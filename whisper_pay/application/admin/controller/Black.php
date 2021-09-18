<?php
/**
 * User: nickbai
 * Date: 2017/10/23 13:33
 * Email: 1902822973@qq.com
 */
namespace app\admin\controller;

class Black extends Base
{
    // 黑名单
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['customer_ip'] = $param['searchText'];
            }

            $result = db('black_list')->where($where)->limit($offset, $limit)->select();
            foreach($result as $key=>$vo){
                // 生成操作按钮
                $result[$key]['operate'] = $this->makeBtn($vo['black_id']);
            }

            $return['total'] = db('black_list')->where($where)->count();  //总数据
            $return['rows'] = $result;

            return json($return);
        }

        return $this->fetch();
    }

    // 删除黑名单
    public function delBlackList()
    {
        if (request()->isAjax()) {

            $id = input('param.id');

            db('black_list')->where('black_id', $id)->delete();
            return json(['code' => 0, 'data' => '', 'msg' => '删除成功']);
        }
    }

    // 生成按钮
    private function makeBtn($id)
    {
        $operate = '<a href="javascript:blackDel(' . $id . ')"><button type="button" class="btn btn-danger btn-sm">';
        $operate .= '<i class="fa fa-trash-o"></i> 删除</button></a> ';

        return $operate;
    }
}