<?php
/**
 * User: nickbai
 * Date: 2017/10/23 13:33
 * Email: 1902822973@qq.com
 */
namespace app\admin\controller;

class Notice extends Base
{
    // 系统公告
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['title'] = $param['searchText'];
            }

            $result = db('notice')->where($where)->limit($offset, $limit)->select();
            foreach($result as $key=>$vo){
                // 生成操作按钮
                $result[$key]['operate'] = $this->makeBtn($vo['id']);
            }

            $return['total'] = db('notice')->where($where)->count();  //总数据
            $return['rows'] = $result;

            return json($return);
        }

        return $this->fetch();
    }

    // 添加系统公告
    public function addNotice()
    {
        if(request()->isPost()){

            $param = input('post.');
            $param['title'] = trim($param['title']);

            $has = db('notice')->field('id')->where('title', $param['title'])->find();
            if(!empty($has)){
                return json(['code' => -1, 'data' => '', 'msg' => '该公告已经存在']);
            }

            try{
                db('notice')->insert($param);
            }catch(\Exception $e){
                return json(['code' => -2, 'data' => '', 'msg' => $e->getMessage()]);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '添加系统公告成功']);
        }

        return $this->fetch();
    }

    // 编辑系统公告
    public function editNotice()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $param['title'] = trim($param['title']);

            // 检测用户修改的系统公告是否重复
            $has = db('notice')->where('title', $param['title'])->where('id', '<>', $param['id'])->find();
            if(!empty($has)){
                return json(['code' => -1, 'data' => '', 'msg' => '该系统公告已经存在']);
            }

            try{
                db('notice')->where('id', $param['id'])->update($param);
            }catch(\Exception $e){
                return json(['code' => -2, 'data' => '', 'msg' => $e->getMessage()]);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '编辑系统公告成功']);
        }

        $id = input('param.id/d');
        $info = db('notice')->where('id', $id)->find();

        $this->assign([
            'info' => $info
        ]);
        return $this->fetch();
    }

    // 删除系统公告
    public function delNotice()
    {
        if(request()->isAjax()){
            $id = input('param.id/d');

            try{
                db('notice')->where('id', $id)->delete();
            }catch(\Exception $e){
                return json(['code' => -1, 'data' => '', 'msg' => $e->getMessage()]);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '删除系统公告成功']);
        }
    }

    // 生成按钮
    private function makeBtn($id)
    {
        $operate = '<a href="' . url('notice/editNotice', ['id' => $id]) . '">';
        $operate .= '<button type="button" class="btn btn-primary btn-sm"><i class="fa fa-paste"></i> 编辑</button></a> ';

        $operate .= '<a href="javascript:noticeDel(' . $id . ')"><button type="button" class="btn btn-danger btn-sm">';
        $operate .= '<i class="fa fa-trash-o"></i> 删除</button></a> ';

        return $operate;
    }
}