<?php
namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    // pc客户端
    public function chat()
    {
        $id = input('param.id');
        $name = input('param.name');
        $avatar = input('param.avatar');
        $group = input('param.group');

        if (empty($id)) {
            if (empty(session('id'))) {
                $id = uniqid();
                session('id', $id);
            } else {
                $id = session('id');
            }
        }

        if (empty($name)) {
            if (empty(session('name'))) {
                $name = '访客' . $id;
                session('name', $name);
            } else {
                $name = session('name');
            }
        }

        if (empty($avatar)) {
            if (empty(session('avatar'))) {
                $avatar = '/static/admin/images/profile_small_2.jpg';
                session('avatar', $avatar);
            } else {
                $avatar = session('avatar');
            }
        }

        if (empty($group)) {
            if (empty(session('group'))) {
                $group = 1;
                session('group', $group);
            } else {
                $group = session('group');
            }
        }

        // 跳转到移动端
        if(request()->isMobile()){
            $param = http_build_query([
                'id' => $id,
                'name' => $name,
                'group' => $group,
                'avatar' => $avatar
            ]);
            $this->redirect('/index/index/mobile?' . $param);
        }

        $this->assign([
            'socket' => config('protocol') . config('socket'),
            'id' => $id,
            'name' => $name,
            'group' => $group,
            'avatar' => $avatar,
            'question' => db('questions')->field('id,question')->select(),
            'notice' => db('notice')->field('id,title')->select()
        ]);

        return $this->fetch();
    }

    // pc客户端
    public function chatV2()
    {
        if (empty(session('id'))) {
            $id = uniqid();
            session('id', $id);
        } else {
            $id = session('id');
        }

        if (empty(session('name'))) {
            $name = '访客' . $id;
            session('name', $name);
        } else {
            $name = session('name');
        }

        if (empty(session('avatar'))) {
            $avatar = '/static/admin/images/profile_small_2.jpg';
            session('avatar', $avatar);
        } else {
            $avatar = session('avatar');
        }

        if (empty(session('group'))) {
            $group = 1;
            session('group', $group);
        } else {
            $group = session('group');
        }

        $this->assign([
            'socket' => config('protocol') . config('socket'),
            'id' => $id,
            'name' => $name,
            'group' => $group,
            'avatar' => $avatar
        ]);

        return $this->fetch();
    }

    // 移动客户端
    public function mobile()
    {
        $this->assign([
            'socket' => config('protocol') . config('socket'),
            'id' => input('param.id'),
            'name' => input('param.name'),
            'group' => input('param.group'),
            'avatar' => input('param.avatar'),
        ]);

        return $this->fetch();
    }

    // 留言
    public function leave()
    {
        if(request()->isAjax()){

            $param = input('post.');
            if(empty($param['username']) || empty($param['phone']) || empty($param['content'])){
                return json(['code' => -1, 'data' => '', 'msg' => '请全部填写']);
            }

            $param['add_time'] = time();

            try{
                db('leave_msg')->insert($param);
            }catch (\Exception $e){
                return json(['code' => -2, 'data' => '', 'msg' => '留言失败']);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '留言成功']);
        }

        return $this->fetch();
    }

    // 常见问题
    public function question()
    {
        $id = input('param.id');

        $question = db('questions')->where('id', $id)->find();

        $this->assign([
            'question' => $question
        ]);

        return $this->fetch();
    }

    // 系统公告
    public function notice()
    {
        $id = input('param.id');

        $question = db('notice')->where('id', $id)->find();

        $this->assign([
            'notice' => $question
        ]);

        return $this->fetch();
    }

    // 绑定点赞
    public function bindPraise()
    {
        if(request()->isAjax()) {

            $kfId = ltrim(input('param.kf_id', 0), 'KF');
            $uerId = input('param.uid', 0);
            $uuid = uniqid();

            try {
                db('praise')->insert([
                    'user_id' => $uerId,
                    'kf_id' => $kfId,
                    'uuid' => $uuid,
                    'star' => 0,
                    'add_time' => date('Y-m-d H:i:s')
                ]);
            }catch (\Exception $e) {
                return json(['code' => -1, 'data' => '', 'res' => 'ERROR']);
            }

            return json(['code' => 1, 'data' => $uuid, 'res' => 'SUCCESS']);
        }
    }

    // 点赞
    public function doPraise()
    {
        if(request()->isAjax()) {

            $uuid = input('param.uuid', 0);
            $star = input('param.star', 0);

            if($star > 0 && 0 != $uuid) {
                try {
                    db('praise')->where('uuid', $uuid)->update([
                        'star' => $star,
                    ]);
                }catch (\Exception $e) {
                    return json(['code' => -1, 'data' => '', 'res' => 'ERROR']);
                }
            }

            return json(['code' => 1, 'data' => '', 'res' => 'SUCCESS']);
        }
    }

    // 获取聊天记录
    public function getChatLog()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = 10; // 一次显示10 条聊天记录
            $offset = ($param['page'] - 1) * $limit;

            $logs = db('chat_log')->where(function($query) use($param){
                $query->where('from_id', $param['uid'])->where('to_id', $param['kf_id']);
            })->whereOr(function($query) use($param){
                $query->where('from_id', $param['kf_id'])->where('to_id', $param['uid']);
            })->limit($offset, $limit)->order('id', 'desc')->select();

            $total =  db('chat_log')->where(function($query) use($param){
                $query->where('from_id', $param['uid'])->where('to_id', $param['kf_id']);
            })->whereOr(function($query) use($param){
                $query->where('from_id', $param['kf_id'])->where('to_id', $param['uid']);
            })->count();

            foreach($logs as $key=>$vo){

                $logs[$key]['type'] = 'user';
                $logs[$key]['time_line'] = date('Y-m-d H:i:s', $vo['time_line']);

                if($vo['from_id'] == $param['uid']){
                    $logs[$key]['type'] = 'mine';
                }
            }

            return json(['code' => 1, 'data' => $logs, 'msg' => intval($param['page']), 'total' => ceil($total / $limit)]);
        }
    }
}
