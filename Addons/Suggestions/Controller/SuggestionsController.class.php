<?php

namespace Addons\Suggestions\Controller;
use Home\Controller\AddonsController;

class SuggestionsController extends AddonsController{
	var $model;
	function _initialize() {
		$this->model = $this->getModel ( 'servicer' );
		parent::_initialize ();
		$controller = strtolower ( _CONTROLLER );

		$nav_list = [];
		$res ['title'] = '建议列表';
		$res ['url'] = addons_url ( 'Suggestions://Suggestions/lists' );
		$res ['class'] = ($controller == 'Suggestions' && _ACTION == "lists") ? 'current' : '';
		array_push($nav_list,$res);

		$res ['title'] = '功能配置';
		$res ['url'] = addons_url ( 'Suggestions://Suggestions/config' );
		$res ['class'] = ($controller == 'Suggestions' && _ACTION == "config") ? 'current' : '';
		array_push($nav_list,$res);

		$res ['title'] = '增加数据';
		$res ['url'] = addons_url ( 'Suggestions://Suggestions/add' );
		$res ['class'] = ($controller == 'Suggestions' && _ACTION == "add") ? 'current' : '';
		array_push($nav_list,$res);

		$this->assign ( 'nav', $nav_list );
	}

	function lists()
	{
		$model = $this->getModel();

		$list_data = $this->_get_model_list($model);
		
		$uids = getSubByKey($list_data['list_data'],'uid');
		$uids = array_filter($uids);
		$uids = array_unique($uids);
		if(!empty($uids)){
			$map['uid'] = [
				'in',
				$uids
			];

			$members = M('member')->where($map)->field('uid,nickname,truename,mobile')->select();
			foreach ($members as $m){
				!empty($m['truename']) || $m['truename'] = $m['nickname'];
				$user[$m['uid']] = $m;
			}

			foreach ($list_data['list_data'] as &$vo){
				$vo['mobile'] = $user[$vo['uid']]['mobile'];
				$vo['uid'] = $user[$vo['uid']]['truename'];
			}
		}

		$this->assign($list_data);

		$this->display($model['template_list']);
	}

	function suggestion(){
		$config = getAddonConfig('Suggestions');
		$this->assign($config);
		dump($config);exit;
		$data['uid'] = $this->mid;
		$user = M('member')->where($data)->find();
		$this->assign('user',$user);

		if(IS_POST){
			$truename   =   I('truename');
			if($config['need_truename'] && !empty($truename)){
				$member['truename']     =   $truename;
			}
			$mobile =   I('mobile');
			if($config['need_mobile'] && !empty($mobile)){
				$member['mobile']   =   $mobile;
			}
			if(!empty($member)){
				M('member')->where($data)->save($member);
			}

			$data['cTime']  =   time();
			$data['content']    =   I('content');

			$res = M('suggestions')->add($data);
			if($res){
				$this->success('增加成功，谢谢您的反馈！');
			}else{
				$this->error('增加失败，请稍后再试！');
			}
		}else{
			$this->display();
		}
	}
}
