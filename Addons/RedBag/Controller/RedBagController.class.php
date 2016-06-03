<?php

namespace Addons\RedBag\Controller;

use Home\Controller\AddonsController;

if (! function_exists ( 'copydir' )) {
	// 复制目录，目前用于生成素材
	function copydir($strSrcDir, $strDstDir) {
		$dir = opendir ( $strSrcDir );
		if (! $dir) {
			return false;
		}
		if (! is_dir ( $strDstDir )) {
			if (! mkdir ( $strDstDir )) {
				return false;
			}
		}
		while ( false !== ($file = readdir ( $dir )) ) {
			if ($file == '.' || $file == '..' || $file == '.svn' || $file == '.DS_Store' || $file == '__MACOSX' || $file == 'Thumbs.db' || $file == 'Thumbs.db') {
				continue;
			}
			if (is_dir ( $strSrcDir . '/' . $file )) {
				if (! copydir ( $strSrcDir . '/' . $file, $strDstDir . '/' . $file )) {
					return false;
				}
			} else {
				if (! copy ( $strSrcDir . '/' . $file, $strDstDir . '/' . $file )) {
					return false;
				}
			}
		}
		closedir ( $dir );
		return true;
	}
}
defined ( 'ADDON_BASE_PATH' ) or define ( 'ADDON_BASE_PATH', SITE_PATH . '/Addons/RedBag' );
class RedBagController extends AddonsController {
	function _initialize() {
		$act = strtolower ( _ACTION );
		
		$res ['title'] = '微信红包';
		$res ['url'] = addons_url ( 'RedBag://RedBag/lists' );
		$res ['class'] = $act != 'config' ? 'current' : '';
		$nav [] = $res;
		
		$res ['title'] = '商户配置';
		$res ['url'] = addons_url ( 'RedBag://RedBag/config' );
		$res ['class'] = $act == 'config' ? 'current' : '';
		$nav [] = $res;
		
		$this->assign ( 'nav', $nav );
	}
	function lists() {
// 		$model = $this->getModel ( 'redbag' );
// 		parent::lists ( $model );

	    $isAjax = I ( 'isAjax' );
	    $isRadio = I ( 'isRadio' );
	    $model = $this->getModel ( 'redbag' );
	    $list_data = $this->_get_model_list ( $model, 0, 'id desc', true );
	    // 		判断该活动是否已经设置投票调查
	    if ($isAjax) {
	        $this->assign('isRadio',$isRadio);
	        $this->assign ( $list_data );
	        $this->display ( 'ajax_lists_data' );
	    } else {
	        $this->assign ( $list_data );
	        $this->display ();
	    }
	}
	function list_data() {
        //$page = I ( 'p', 1, 'intval' );
        $map['token']=get_token();
        $map['aim_table']='lottery_games';
        $dao=D ( 'Addons://RedBag/RedBag' );
        $list_data =$dao->where($map)->field('id')->order ( 'id DESC' )->select();
       
        foreach ($list_data as &$v){
            $v=$dao->getInfo($v['id']);
            $v['background']=get_cover_url($v['background']);
            $v['title']=$v['act_name'];
            $v['num']=$v['total_num'];
        }
        $list_data['list_data']=$list_data;
//         dump ( $list_data );
        $this->ajaxReturn( $list_data ,'JSON');
    }
	function add() {
		$model = $this->getModel ( 'redbag' );
		if (IS_POST) {
			$this->checkPostData ();
		}
		parent::add ( $model );
	}
	function checkPostData() {
		// if (! I ( 'post.mch_id' )) {
		// $this->error ( '商户号不能为空' );
		// }
		// if (! I ( 'post.wxappid' )) {
		// $this->error ( '公众账号appid不能为空' );
		// }
		if (! I ( 'post.nick_name' )) {
			$this->error ( '提供方名称不能为空' );
		}
		if (! I ( 'post.send_name' )) {
			$this->error ( '商户名称不能为空' );
		}
		if (I ( 'post.total_amount' ) <= 0) {
			$this->error ( '付款金额应大于0' );
		}
		if (I ( 'post.total_amount' ) < I ( 'post.min_value' )) {
			$this->error ( '付款金额应大于最小红包金额' );
		}
		if (I ( 'post.total_amount' ) < I ( 'post.max_value' )) {
			$this->error ( '付款金额应大于最大红包金额' );
		}
		if (I ( 'post.min_value' ) <= 0) {
			$this->error ( '最小红包金额应大于0' );
		}
		if (I ( 'post.max_value' ) <= 0) {
			$this->error ( '最大红包金额应大于0' );
		}
		if (I ( 'post.total_num' ) <= 0) {
			$this->error ( '红包发放总人数应大于0' );
		}
		if (mb_strlen ( I ( 'post.wishing' ), 'UTF-8' ) > 25) {
			$this->error ( '红包祝福语不超过25个字！' );
		}
		if (mb_strlen ( I ( 'post.act_name' ), 'UTF-8' ) > 25) {
			$this->error ( '活动名称不超过25个字！' );
		}
		if (I ( 'post.collect_limit' ) < 0) {
			$this->error ( '每人最多领取次数不能小于0' );
		}
	}
	function edit() {
		
	

	    $id = I ( 'id' );
	    $model = $this->getModel ( 'redbag' );
	    // 获取数据
	    $data = M(get_table_name($model['id']))->find($id);
	    $data || $this->error('数据不存在！');

	    if (IS_POST) {
			$this->checkPostData ();
			
	        $Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
	        // 获取模型的字段信息
	        $Model = $this->checkAttr ( $Model, $model ['id'] );
	       	$res = false;
			$Model->create () && $res=$Model->save ();
			if ($res !== false) {
				D ( 'Addons://RedBag/RedBag' )->getInfo ( $id, true );
	            // 清空缓存
	            method_exists ( $Model, 'clear' ) && $Model->clear ( $id, 'edit' );

	            $this->success ( '保存' . $model ['title'] . '成功！', U ( 'lists?model=' . $model ['name'], $this->get_param ) );
	        } else {
	            $this->error ( $Model->getError () );
	        }
	    } else {
	       $fields = get_model_attribute ( $model ['id'] );
			$this->assign ( 'fields', $fields );
			$this->assign ( 'data', $data );
			
			$templateFile || $templateFile = $model ['template_edit'] ? $model ['template_edit'] : '';
			$this->display ( $templateFile );
	    }
	}
	function preview() {
		$id = I ( 'id', 0, 'intval' );
		$url = addons_url ( 'RedBag://Wap/index', array (
				'id' => $id 
		) );
		$this->assign ( 'url', $url );
		$this->display ( SITE_PATH . '/Application/Home/View/default/Addons/preview.html' );
	}
	function test() {
		$str = '<xml>
<return_code><![CDATA[SUCCESS]]></return_code>
<return_msg><![CDATA[获取成功]]></return_msg>
<result_code><![CDATA[SUCCESS]]></result_code>
<mch_id>10000098</mch_id>
<appid><![CDATA[wxe062425f740c30d8]]></appid>
<detail_id><![CDATA[1000000000201503283103439304]]></detail_id>
<mch_billno><![CDATA[1000005901201407261446939628]]></mch_billno>
<status><![CDATA[RECEIVED]]></status>
<send_type><![CDATA[API]]></send_type>
<hb_type><![CDATA[GROUP]]></hb_type>
<total_num>4</total_num>
<total_amount>650</total_amount>
<send_time><![CDATA[2015-04-21 20:00:00]]></send_time>
<wishing><![CDATA[开开心心]]></wishing>
<remark><![CDATA[福利]]></remark>
<act_name><![CDATA[福利测试]]></act_name>
<hblist>
<hbinfo>
<openid><![CDATA[ohO4GtzOAAYMp2yapORH3dQB3W18]]></openid>
<status><![CDATA[RECEIVED]]></status>
<amount>1</amount>
<rcv_time><![CDATA[2015-04-21 20:00:00]]></rcv_time>
</hbinfo>
<hbinfo>
<openid><![CDATA[ohO4GtzOAAYMp2yapORH3dQB3W17]]></openid>
<status><![CDATA[RECEIVED]]></status>
<amount>1</amount>
<rcv_time><![CDATA[2015-04-21 20:00:00]]></rcv_time>
</hbinfo>
<hbinfo>
<openid><![CDATA[ohO4GtzOAAYMp2yapORH3dQB3W16]]></openid>
<status><![CDATA[RECEIVED]]></status>
<amount>1</amount>
<rcv_time><![CDATA[2015-04-21 20:00:00]]></rcv_time>
</hbinfo>
<hbinfo>
<openid><![CDATA[ohO4GtzOAAYMp2yapORH3dQB3W15]]></openid>
<status><![CDATA[RECEIVED]]></status>
<amount>1</amount>
<rcv_time><![CDATA[2015-04-21 20:00:00]]></rcv_time>
</hbinfo>
</hblist>
</xml> ';
		$str2 = '

<xml>

<return_code><![CDATA[SUCCESS]]></return_code>

<return_msg><![CDATA[发放成功.]]></return_msg>

<result_code><![CDATA[SUCCESS]]></result_code>

<err_code><![CDATA[0]]></err_code>

<err_code_des><![CDATA[发放成功.]]></err_code_des>

<mch_billno><![CDATA[0010010404201411170000046545]]></mch_billno>

<mch_id>10010404</mch_id>

<wxappid><![CDATA[wx6fa7e3bab7e15415]]></wxappid>

<re_openid><![CDATA[onqOjjmM1tad-3ROpncN-yUfa6uI]]></re_openid>

<total_amount>1</total_amount>

<send_listid>100000000020150520314766074200</send_listid>

<send_time>20150520102602</send_time>

</xml>
		';
		
		$data = new \SimpleXMLElement ( $str );
		dump ( object_array ( $data ) );
		$array = json_decode ( json_encode ( $data, TRUE ) );
		foreach ( $data as $key => $value ) {
			// dump ( gettype ( $value ) );
			$msg [$key] = safe ( strval ( $value ) );
		}
		dump ( $array );
	}

}
function object_array($array) {
	if (is_object ( $array )) {
		$array = ( array ) $array;
	}
	if (is_array ( $array )) {
		foreach ( $array as $key => $value ) {
			$array [$key] = object_array ( $value );
		}
	}
	return $array;
}