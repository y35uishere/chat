<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 当前系统版本
    'version' => 'v1.2.5 LTS',

    // 加密盐
    'salt' => '~NickBai!@#',

    // 通信协议
    'protocol' => 'ws://',

    // socket server
    'socket' => '127.0.0.1:8282',

    // 微信或者下程序客服地址
    'wx_kf_url' => '',

    // 管理员登录时间
    'save_time' => 86400,

    // 应用命名空间
    'app_namespace'          => 'app',
    // 应用调试模式
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false
];