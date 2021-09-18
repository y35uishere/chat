<?php
/**
 * User: nickbai
 * Date: 2017/10/23 13:33
 * Email: 1902822973@qq.com
 */
namespace app\admin\controller;

class Question extends Base
{
    // 常见问题
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['question'] = $param['searchText'];
            }

            $result = db('questions')->where($where)->limit($offset, $limit)->select();
            foreach($result as $key=>$vo){
                // 生成操作按钮
                $result[$key]['operate'] = $this->makeBtn($vo['id']);
            }

            $return['total'] = db('questions')->where($where)->count();  //总数据
            $return['rows'] = $result;

            return json($return);
        }

        return $this->fetch();
    }

    // 添加常用语
    public function addQuestion()
    {
        if(request()->isPost()){

            $param = input('post.');
            $param['question'] = trim($param['question']);

            $has = db('questions')->field('id')->where('question', $param['question'])->find();
            if(!empty($has)){
                return json(['code' => -1, 'data' => '', 'msg' => '该常见问题已经存在']);
            }

            try{
                db('questions')->insert($param);
            }catch(\Exception $e){
                return json(['code' => -2, 'data' => '', 'msg' => $e->getMessage()]);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '添加常见问题成功']);
        }

        return $this->fetch();
    }

    // 编辑常用语
    public function editQuestion()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $param['question'] = trim($param['question']);

            // 检测用户修改的常用语是否重复
            $has = db('questions')->where('question', $param['question'])->where('id', '<>', $param['id'])->find();
            if(!empty($has)){
                return json(['code' => -1, 'data' => '', 'msg' => '该常见问题已经存在']);
            }

            try{
                db('questions')->where('id', $param['id'])->update($param);
            }catch(\Exception $e){
                return json(['code' => -2, 'data' => '', 'msg' => $e->getMessage()]);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '编辑常见问题成功']);
        }

        $id = input('param.id/d');
        $info = db('questions')->where('id', $id)->find();

        $this->assign([
            'info' => $info
        ]);
        return $this->fetch();
    }

    // 删除常用语
    public function delQuestion()
    {
        if(request()->isAjax()){
            $id = input('param.id/d');

            try{
                db('questions')->where('id', $id)->delete();
            }catch(\Exception $e){
                return json(['code' => -1, 'data' => '', 'msg' => $e->getMessage()]);
            }

            return json(['code' => 1, 'data' => '', 'msg' => '删除常见问题成功']);
        }
    }

    // 生成按钮
    private function makeBtn($id)
    {
        $operate = '<a href="' . url('question/editQuestion', ['id' => $id]) . '">';
        $operate .= '<button type="button" class="btn btn-primary btn-sm"><i class="fa fa-paste"></i> 编辑</button></a> ';

        $operate .= '<a href="javascript:questionDel(' . $id . ')"><button type="button" class="btn btn-danger btn-sm">';
        $operate .= '<i class="fa fa-trash-o"></i> 删除</button></a> ';

        return $operate;
    }
}