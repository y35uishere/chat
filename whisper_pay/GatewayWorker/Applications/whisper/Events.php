<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);
use \GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;
use Workerman\Worker;
use Workerman\WebServer;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 进程启动后初始化数据库连接
     */
    public static function onWorkerStart($worker)
    {
        \think\Db::setConfig([
            // 数据库类型
            'type'            => 'mysql',
            // 服务器地址
            'hostname'        => '127.0.0.1',
            // 数据库名
            'database'        => 'whisper',
            // 用户名
            'username'        => 'root',
            // 密码
            'password'        => 'root',
            // 端口
            'hostport'        => '3306',
            // 数据库编码默认采用utf8
            'charset'         => 'utf8',
            // 断线重连
            'break_reconnect' => true,
        ]);

        // 监听一个http端口
        $inner_http_worker = new Worker('http://0.0.0.0:2121');
        // 当http客户端发来数据时触发
        $inner_http_worker->onMessage = function($http_connection, $data){

            $content = $data['post']['content'];
            Gateway::sendToUid($data['post']['to'], json_encode([
                'message_type' => 'chatMessage',
                'data' => [
                    'name' => $content['name'],
                    'avatar' => $content['avatar'],
                    'id' => $content['id'],
                    'ip' => $content['ip'],
                    'time' => date('H:i'),
                    'content' => htmlspecialchars($content['message']),
                    'protocol' => 'http'
                ]
            ]));

            // 聊天信息入库
            $serviceLog = [
                'from_id' => $content['id'],
                'from_name' => $content['name'],
                'from_avatar' => $content['avatar'],
                'to_id' => $data['post']['to'],
                'to_name' => $data['post']['to_name'],
                'content' => htmlspecialchars($content['message']),
                'time_line' => time()
            ];

            \think\Db::table('ws_chat_log')->insert($serviceLog);
            unset($serviceLog);

            // 更新用户信息
            $has = \think\Db::table('ws_customer')->where('customer_id', $content['id'])->find();

            $customer = [
                'customer_id' => $content['id'],
                'customer_name' => $content['name'],
                'avatar' => $content['avatar'],
                'ip' => $content['ip'],
                'group_id' => $content['group'],
                'client_id' => 0,
                'add_time' => date('Y-m-d H:i:s'),
                'online' => 2 // 离线
            ];

            if(!empty($has)) {
                \think\Db::table('ws_customer')->where('customer_id', $content['id'])->update($customer);
            }else {
                \think\Db::table('ws_customer')->insert($customer);
            }

            // 服务信息
            $check = \think\Db::table('ws_service_log')->where('user_id', $content['id'])
                ->where('kf_id', ltrim($data['post']['to'], 'KF'))
                ->where('start_time', '>', date('Y-m-d'))
                ->where('start_time', '<', date('Y-m-d') . ' 23:59:59')
                ->find();

            if(empty($check)) {

                \think\Db::table('ws_service_log')->insert([
                    'user_id' => $content['id'],
                    'client_id' => 0,
                    'user_name' => $content['name'],
                    'user_ip' => $content['ip'],
                    'user_avatar' => $content['avatar'],
                    'kf_id' => ltrim($data['post']['to'], 'KF'),
                    'start_time' => date('Y-m-d H:i:s'),
                    'end_time' => date('Y-m-d H:i:s'),
                    'group_id' => $content['group'],
                    'protocol' => 'http'
                ]);
            }else {

                \think\Db::table('ws_service_log')->where('log_id', $check['log_id'])->update([
                    'end_time' => date('Y-m-d H:i:s'),
                    'can_show' => 0,
                    'user_ip' => $content['ip']
                ]);
            }

            return $http_connection->send(json_encode(['code' => 200, 'data' => '', 'msg' => 'ok']));
        };
        // 执行监听
        $inner_http_worker->listen();

        // 1分钟统计一次实时数据
        Timer::add(60 * 1, function() {
            self::writeLog(1);
        });

        // 2分钟写一次当前日期点数的log数据
        Timer::add(60 * 2, function() {
            self::writeLog(2);
        });
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        // 检测黑名单
        $isIn = \think\Db::table('ws_black_list')->field('black_id')->where('customer_ip', $ip)->find();

        if (!empty($isIn)) {

            $waitMessage = [
                'message_type' => 'wait',
                'data' => [
                    'content' => '暂时没有客服上班,请稍后再咨询。',
                ]
            ];
            Gateway::sendToClient($client_id, json_encode($waitMessage));
            unset($waitMessage);
            return false;
        }
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        $message = json_decode($message, true);
        switch ($message['type']) {
            // 客服初始化
            case 'init':
                // 绑定 client_id 和 uid
                Gateway::bindUid($client_id, $message['uid']);
                $_SESSION['uid'] = $message['uid'];
                // 设置客服在线
                \think\Db::table('ws_users')->where('id', ltrim($message['uid'], 'KF'))->setField('online', 1);
                // 尝试拉取用户来服务
                self::userOfflineTask($message['group']);

                break;
            // 顾客初始化
            case 'userInit';
                $has = \think\Db::table('ws_customer')->where('customer_id', $message['uid'])->find();

                $customer = [
                    'customer_id' => $message['uid'],
                    'customer_name' => $message['name'],
                    'avatar' => $message['avatar'],
                    'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
                    'group_id' => $message['group'],
                    'client_id' => $client_id,
                    'add_time' => date('Y-m-d H:i:s'),
                    'online' => 1
                ];

                if(!empty($has)) {
                    \think\Db::table('ws_customer')->where('customer_id', $message['uid'])->update($customer);
                }else {
                    \think\Db::table('ws_customer')->insert($customer);
                }

                // 写入累计接入量
                $total = \think\Db::table('ws_total_in')->field('id')->where('now_day', date('Y-m-d'))->find();
                if(empty($total)) {
                    \think\Db::table('ws_total_in')->insert(['now_day' => date('Y-m-d'), 'total_in' => 1]);
                }else {
                    \think\Db::table('ws_total_in')->where('id', $total['id'])->setInc('total_in');
                }

                // 绑定 client_id 和 uid
                Gateway::bindUid($client_id, $message['uid']);
                $_SESSION['uid'] = $message['uid'];
                $_SESSION['group'] = $message['group'];

                // 尝试分配新会员进入服务
                self::userOnlineTask($customer);
                unset($customer);
                break;
            // 聊天
            case 'chatMessage':
                $client = Gateway::getClientIdByUid($message['data']['to_id']);

                if(!empty($client)){
                    $chat_message = [
                        'message_type' => 'chatMessage',
                        'data' => [
                            'name' => $message['data']['from_name'],
                            'avatar' => $message['data']['from_avatar'],
                            'id' => $message['data']['from_id'],
                            'time' => date('H:i'),
                            'content' => htmlspecialchars($message['data']['content']),
                            'protocol' => 'ws'
                        ]
                    ];
                    Gateway::sendToClient($client['0'], json_encode($chat_message));
                    unset($chat_message);
                }

                // 聊天信息入库
                $serviceLog = [
                    'from_id' => $message['data']['from_id'],
                    'from_name' => $message['data']['from_name'],
                    'from_avatar' => $message['data']['from_avatar'],
                    'to_id' => $message['data']['to_id'],
                    'to_name' => $message['data']['to_name'],
                    'content' => $message['data']['content'],
                    'time_line' => time()
                ];

                \think\Db::table('ws_chat_log')->insert($serviceLog);
                unset($serviceLog);
                break;
            // 转接
            case 'changeGroup':
                $client = Gateway::getClientIdByUid($message['uid']);
                // 此时用户离线
                if(!isset($client['0'])) {
                    return false;
                }

                // 通知客户端转接中
                $reLink = [
                    'message_type' => 'relinkMessage'
                ];
                Gateway::sendToUid($message['uid'], json_encode($reLink));
                unset($reLink);

                // 记录该客服与该会员的服务结束
                \think\Db::table('ws_service_log')->where('user_id', $message['uid'])
                    ->where('kf_id', ltrim($message['old_kf_id'], 'KF'))
                    ->where('client_id', $client['0'])
                    ->setField('end_time', date('Y-m-d H:i:s'));

                // 开始分配 -- 目前转接无视最大服务人数，直接转入
                \think\Db::table('ws_service_queue')->where('customer_id', $message['uid'])
                    ->update([
                        'kf_id' => $message['kf_id'],
                        'kf_name' => $message['kf_name'],
                        'group_id' => $message['group_id']
                    ]);

                \think\Db::table('ws_service_log')->insert([
                    'user_id' => $message['uid'],
                    'client_id' => $client['0'],
                    'user_name' => $message['name'],
                    'user_ip' => $message['ip'],
                    'user_avatar' => $message['avatar'],
                    'kf_id' => $message['kf_id'],
                    'start_time' => date('Y-m-d H:i:s'),
                    'group_id' => $message['group_id']
                ]);

                // 通知会员发送信息绑定客服的id
                $noticeUser = [
                    'message_type' => 'connect',
                    'data' => [
                        'kf_id' => 'KF' . $message['kf_id'],
                        'kf_name' => $message['kf_name']
                    ]
                ];
                Gateway::sendToUid($message['uid'], json_encode($noticeUser));
                unset($noticeUser);

                // 通知客服端绑定会员的信息
                $noticeKf = [
                    'message_type' => 'connect',
                    'data' => [
                        'user_info' => [
                            'id' => $message['uid'],
                            'name' => $message['name'],
                            'avatar' => $message['avatar'],
                            'ip' => $message['ip'],
                            'end_time' => '0000-00-00 00:00:00'
                        ]
                    ]
                ];
                Gateway::sendToUid('KF' . $message['kf_id'], json_encode($noticeKf));
                unset($noticeKf);

                break;
            case 'closeUser':
                $waitMessage = [
                    'message_type' => 'wait',
                    'data' => [
                        'content' => '暂时没有客服上班,请稍后再咨询。',
                    ]
                ];
                Gateway::sendToUid($message['uid'], json_encode($waitMessage));
                unset($waitMessage);

                break;
            case 'closeKf':

                $uid = ltrim($message['uid'], 'KF');
                \think\Db::table('ws_users')->where('id', $uid)->setField('online', 2);
                break;
        }
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        $uid = isset($_SESSION['uid']) ? $_SESSION['uid'] : 0;

        if(0 === $uid) {
            return ;
        }

        // 客服退出不处理,客服应该走打开下班退出【兼容客服刷新浏览器导致的退出】
        if(strpos($uid, 'KF') !== false) {
            return ;
        }

        $serviceInfo = \think\Db::table('ws_service_queue')->field('kf_id')->where('customer_id', $uid)->find();
        // 从服务队列中移除信息
        \think\Db::table('ws_service_queue')->where('customer_id', $uid)->delete();
        // 维护退出时间
        \think\Db::table('ws_service_log')->where('user_id', $uid)->where('kf_id', $serviceInfo['kf_id'])
            ->where('client_id', $client_id)->setField('end_time', date('Y-m-d H:i:s'));
        // 用户下线
        \think\Db::table('ws_customer')->where('customer_id', $uid)->where('client_id', $client_id)->setField('online', 2);
        // 通知 客服删除退出的用户[v1.2.2变为置灰头像]
        $del_message = [
            'message_type' => 'offline',
            'data' => [
                'id' => $uid
            ]
        ];

        Gateway::sendToUid('KF' . $serviceInfo['kf_id'], json_encode($del_message));
        unset($del_message);

        self::userOfflineTask($_SESSION['group']);
    }


    /**
     * 有人退出
     * @param $group
     */
    private static function userOfflineTask($group)
    {
        // 查询看该组内还有没有排队的用户
        $customers = \think\Db::table('ws_service_queue')->where('group_id', $group)->where('status', 1)->select();
        if(empty($customers)) {
            return ;
        }

        $customer = array_shift($customers);
        $customer = \think\Db::table('ws_customer')->where('customer_id', $customer['customer_id'])->find();
        // 查询最大的可服务人数
        $maxNumber = self::getMaxServiceNum();
        $res = self::assignmentTask($customer, $maxNumber, 'offline');

        if(1 == $res['code']) {

            // 通知会员发送信息绑定客服的id
            $noticeUser = [
                'message_type' => 'connect',
                'data' => [
                    'kf_id' => 'KF' . $res['data']['id'],
                    'kf_name' => $res['data']['user_name']
                ]
            ];
            Gateway::sendToUid($customer['customer_id'], json_encode($noticeUser));
            unset($noticeUser);

            // 通知客服端绑定会员的信息
            $noticeKf = [
                'message_type' => 'connect',
                'data' => [
                    'user_info' => [
                        'id' => $customer['customer_id'],
                        'name' => $customer['customer_name'],
                        'avatar' => $customer['avatar'],
                        'ip' => $customer['ip'],
                        'end_time' => '0000-00-00 00:00:00'
                    ]
                ]
            ];
            Gateway::sendToUid('KF' . $res['data']['id'], json_encode($noticeKf));
            unset($noticeKf);

            // 逐一通知
            foreach($customers as $number => $vo) {

                $number = $number + 1;
                $waitMsg = '您前面还有 ' . $number . ' 位会员在等待。';
                $waitMessage = [
                    'message_type' => 'wait',
                    'data' => [
                        'content' => $waitMsg,
                    ]
                ];

                Gateway::sendToUid($vo['customer_id'], json_encode($waitMessage));
            }
            unset($waitMessage, $number);
        }else{

            switch ($res['code']) {

                case -1:
                    $waitMsg = '暂时没有客服上班,请稍后再咨询。';
                    // 逐一通知
                    foreach($customers as $vo){

                        $waitMessage = [
                            'message_type' => 'wait',
                            'data' => [
                                'content' => $waitMsg,
                            ]
                        ];
                        Gateway::sendToUid($vo['customer_id'], json_encode($waitMessage));
                    }
                    break;
                case -2:
                    break;
                case -3:
                    break;
                case -4:
                    // 逐一通知
                    foreach($customers as $number => $vo) {

                        $number = $number + 1;
                        $waitMsg = '您前面还有 ' . $number . ' 位会员在等待。';
                        $waitMessage = [
                            'message_type' => 'wait',
                            'data' => [
                                'content' => $waitMsg,
                            ]
                        ];

                        Gateway::sendToUid($vo['customer_id'], json_encode($waitMessage));
                    }
                    break;
            }
            unset($waitMessage, $number);
        }
    }

    /**
     * 有人进入执行分配
     * @param $customer
     */
    private static function userOnlineTask($customer)
    {
        // 查询最大的可服务人数
        $maxNumber = self::getMaxServiceNum();
        $res = self::assignmentTask($customer, $maxNumber);

        if(1 == $res['code']) {

            // 通知会员发送信息绑定客服的id
            $noticeUser = [
                'message_type' => 'connect',
                'data' => [
                    'kf_id' => 'KF' . $res['data']['id'],
                    'kf_name' => $res['data']['user_name']
                ]
            ];
            Gateway::sendToUid($customer['customer_id'], json_encode($noticeUser));
            unset($noticeUser);

            // 检测是否开启自动应答
            $sayHello = \think\Db::table('ws_reply')->field('word,status')->where('id', 1)->find();
            if(!empty($sayHello) && 1 == $sayHello['status']){

                $hello = [
                    'message_type' => 'helloMessage',
                    'data' => [
                        'name' => $res['data']['user_name'],
                        'avatar' => $res['data']['user_avatar'],
                        'id' => $res['data']['id'],
                        'time' => date('H:i'),
                        'content' => htmlspecialchars($sayHello['word'])
                    ]
                ];
                Gateway::sendToUid($customer['customer_id'], json_encode($hello));
                unset($hello);
            }
            unset($sayHello);

            // 通知客服端绑定会员的信息
            $noticeKf = [
                'message_type' => 'connect',
                'data' => [
                    'user_info' => [
                        'id' => $customer['customer_id'],
                        'name' => $customer['customer_name'],
                        'avatar' => $customer['avatar'],
                        'ip' => $customer['ip'],
                        'end_time' => '0000-00-00 00:00:00'
                    ]
                ]
            ];
            Gateway::sendToUid('KF' . $res['data']['id'], json_encode($noticeKf));
            unset($noticeKf);
        }else{

            $waitMsg = '';
            switch ($res['code']) {

                case -1:
                    $waitMsg = '暂时没有客服上班,请稍后再咨询。';
                    break;
                case -2:
                    break;
                case -3:
                    break;
                case -4:
                    if(0 == $res['data']) {
                        $number = 1;
                    }else {
                        $number = $res['data'] + 1;
                    }

                    $waitMsg = '您前面还有 ' . $number . ' 位会员在等待。';
                    break;
            }

            $waitMessage = [
                'message_type' => 'wait',
                'data' => [
                    'content' => $waitMsg,
                ]
            ];

            Gateway::sendToUid($customer['customer_id'], json_encode($waitMessage));
            unset($waitMessage);
        }
    }


    /**
     * 给客服分配会员【均分策略】
     * @param $customer
     * @param $total
     * @param $flag
     */
    private static function assignmentTask($customer, $total, $flag = 'online')
    {
        // 没有待分配的会员
        if(empty($customer)) {
            return ['code' => -2];
        }

        // 未设置每个客服可以服务多少人
        if(0 == $total){
            return ['code' => -3];
        }

        // 查询在线客服信息
        $onlineKf = \think\Db::table('ws_users')->where('group_id', $customer['group_id'])->where('online', 1)->select();
        if(empty($onlineKf)) {
            return ['code' => -1];
        }

        $newKfArr = [];
        $serviceInfo = [];
        // 当前每个客服的服务数量
        foreach ($onlineKf as $key => $vo) {

            $num = \think\Db::table('ws_service_queue')->where('kf_id', $vo['id'])->where('status', 2)->count();

            if(isset($serviceInfo['num'])){

                if($serviceInfo['num'] > $num){
                    $vo['num'] = $num;
                    $serviceInfo = $vo;
                }
            }else{
                $vo['num'] = $num;
                $serviceInfo = $vo;
            }

            $newKfArr[$vo['id']] = $vo;
        }
        unset($onlineKf);

        // 查询该用户，最近一次分配记录
        $recently = \think\Db::table('ws_service_log')->field('kf_id')
            ->where('user_id', $customer['customer_id'])
            ->order('log_id desc')->find();

        $targetKfId = -1;
        if(!empty($recently)) {
            $targetKfId = $recently['kf_id'];
        }

        if(isset($newKfArr[$targetKfId])) {  // 上次服务的客服在线

            if($newKfArr[$targetKfId]['num'] >= $total) { // 客服正在忙

                // 待服务客服队列的等待人数
                $teamNum = \think\Db::table('ws_service_queue')->where('kf_id', $targetKfId)
                    ->where('group_id', $customer['group_id'])->where('status', 1)->count();

                if('online' == $flag) {

                    \think\Db::table('ws_service_queue')->insert([
                        'kf_id' => $targetKfId,
                        'kf_name' => $newKfArr[$targetKfId]['user_name'],
                        'customer_id' => $customer['customer_id'],
                        'group_id' => $customer['group_id'],
                        'status' => 1
                    ]);
                }

                return ['code' => -4, 'data' => $teamNum];

            }else if($newKfArr[$targetKfId]['num'] < $total) { // 客服可以服务

                if('online' == $flag) {

                    \think\Db::table('ws_service_queue')->insert([
                        'kf_id' => $targetKfId,
                        'kf_name' => $newKfArr[$targetKfId]['user_name'],
                        'customer_id' => $customer['customer_id'],
                        'group_id' => $customer['group_id'],
                        'status' => 2
                    ]);
                }else {

                    \think\Db::table('ws_service_queue')->where('customer_id', $customer['customer_id'])->update([
                        'kf_id' => $targetKfId,
                        'status' => 2
                    ]);
                }

                \think\Db::table('ws_service_log')->insertGetId([
                    'user_id' => $customer['customer_id'],
                    'client_id' => $customer['client_id'],
                    'user_name' => $customer['customer_name'],
                    'user_ip' => $customer['ip'],
                    'user_avatar' => $customer['avatar'],
                    'kf_id' => $targetKfId,
                    'start_time' => date('Y-m-d H:i:s'),
                    'group_id' => $customer['group_id']
                ]);

                return ['code' => 1, 'data' => $newKfArr[$targetKfId]];
            }


        } else if(!isset($newKfArr[$targetKfId])) {  // 上次服务的客服不在线

            if($serviceInfo['num'] >= $total) { // 目前服务数最少的客服也在忙

                // 待服务客服队列的等待人数
                $teamNum = \think\Db::table('ws_service_queue')->where('kf_id', $serviceInfo['id'])
                    ->where('group_id', $customer['group_id'])->where('status', 1)->count();

                if('online' == $flag) {

                    \think\Db::table('ws_service_queue')->insert([
                        'kf_id' => $serviceInfo['id'],
                        'kf_name' => $serviceInfo['user_name'],
                        'customer_id' => $customer['customer_id'],
                        'group_id' => $customer['group_id'],
                        'status' => 1
                    ]);
                }

                return ['code' => -4, 'data' => $teamNum];

            }else if($serviceInfo['num'] < $total) { // 目前服务数最少的客服可以服务

                if('online' == $flag) {

                    \think\Db::table('ws_service_queue')->insert([
                        'kf_id' => $serviceInfo['id'],
                        'kf_name' => $serviceInfo['user_name'],
                        'customer_id' => $customer['customer_id'],
                        'group_id' => $customer['group_id'],
                        'status' => 2
                    ]);
                }else if('offline' == $flag) {

                    \think\Db::table('ws_service_queue')->where('customer_id', $customer['customer_id'])->update([
                        'kf_id' => $serviceInfo['id'],
                        'status' => 2
                    ]);
                }

                \think\Db::table('ws_service_log')->insertGetId([
                    'user_id' => $customer['customer_id'],
                    'client_id' => $customer['client_id'],
                    'user_name' => $customer['customer_name'],
                    'user_ip' => $customer['ip'],
                    'user_avatar' => $customer['avatar'],
                    'kf_id' => $serviceInfo['id'],
                    'start_time' => date('Y-m-d H:i:s'),
                    'group_id' => $customer['group_id']
                ]);

                return ['code' => 1, 'data' => $serviceInfo];
            }
        }
    }

    /**
     * 获取最大的服务人数
     * @return int
     */
    private static function getMaxServiceNum()
    {
        $maxNumber = \think\Db::table('ws_kf_config')->field('max_service')->where('id', 1)->find();
        if(empty($maxNumber)) {
            $maxNumber = 5;
        }else {
            $maxNumber = $maxNumber['max_service'];
        }

        return $maxNumber;
    }

    /**
     * 将内存中的数据写入统计表
     * @param int $flag
     */
    private static function writeLog($flag = 1)
    {
        // 上午 8点 到 22 点开始统计
        if(date('H') < 8 || date('H') > 22) {
            return ;
        }

        $onlineKf = \think\Db::table('ws_users')->where('online', 1)->count();
        $inQueue = \think\Db::table('ws_service_queue')->where('status', 1)->count();
        $nowTalking = \think\Db::table('ws_service_queue')->where('status', 2)->count();
        $totalIn = \think\Db::table('ws_total_in')->field('total_in')->where('now_day', date('Y-m-d'))->find();
        $successIn = \think\Db::table('ws_service_log')->where('start_time', '>', date('Y-m-d'))->count();

        $param = [
            'is_talking' => $nowTalking,
            'in_queue' => $inQueue,
            'online_kf' => $onlineKf,
            'success_in' => $successIn,
            'total_in' => empty($totalIn) ? 0 : $totalIn['total_in'],
            'now_date' => date('Y-m-d')
        ];
        \think\Db::table('ws_now_data')->where('id', 1)->update($param);

        if(2 == $flag) {
            $param = [
                'is_talking' => $nowTalking,
                'in_queue' => $inQueue,
                'online_kf' => $onlineKf,
                'success_in' => $successIn,
                'total_in' =>  empty($totalIn) ? 0 : $totalIn['total_in'],
                'add_date' => date('Y-m-d'),
                'add_hour' => date('H'),
                'add_minute' => date('i'),
            ];
            \think\Db::table('ws_service_data')->insert($param);
        }
        unset($onlineKf, $inQueue, $nowTalking, $totalIn, $successIn, $param);
    }
}