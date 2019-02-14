<?php
namespace jR;
class V{
	private $left_delimiter, $right_delimiter, $template_dir, $compile_dir, $display_file;
	private $template_vals = array();
	
	public function __construct($template_dir, $compile_dir, $left_delimiter = '<{', $right_delimiter = '}>',$display_file = ''){
		$this->left_delimiter = $left_delimiter; 
		$this->right_delimiter = $right_delimiter;
		$this->template_dir = $template_dir;     
		$this->compile_dir  = $compile_dir;
		$this->display_file = $display_file;
	}
	public function render($tempalte_name){
		$complied_file = $this->compile($tempalte_name);
		@ob_start();
		# 将key作为变量 将value赋值给变量key
		extract($this->template_vals, EXTR_SKIP);
		$_view_obj = & $this;
		include $complied_file;
		$content = ob_get_clean();
		if(is_array($GLOBALS['view']['contentReplace'])){
			$content = str_replace(array_keys($GLOBALS['view']['contentReplace']),array_values($GLOBALS['view']['contentReplace']),$content);
		}
		return $content;
	}
	public function assign($mixed, $val = ''){
		if(is_array($mixed)){
			foreach($mixed as $k => $v){
				if($k != '')$this->template_vals[$k] = $v;
			}
		}else{
			if($mixed != '')$this->template_vals[$mixed] = $val;
		}
	}
	public function compile($tempalte_name){
		$file = $this->template_dir.DS.$this->display_file.DS.$tempalte_name;
		if(!file_exists($file)) err('Err: "'.$file.'" is not exists!');
		!is_dir($this->compile_dir) && mkdir($this->compile_dir,0755,true);
		if(!is_writable($this->compile_dir) || !is_readable($this->compile_dir)) err('Err: Directory "'.$this->compile_dir.'" is not writable or readable');
		$complied_file = $this->compile_dir.DS.basename(str_replace([DS,'\\','/'],'_',$tempalte_name)).'.'.filemtime($file).'.'.md5(realpath($file)).'.php';
		if(file_exists($complied_file))return $complied_file;
		$template_data = file_get_contents($file); 
		$template_data = $this->_compile_struct($template_data);
		$template_data = $this->_compile_function($template_data);
		$template_data = "<?php if(!class_exists('\jR\V', false)) exit('no direct access allowed');?>\n".$template_data;
		$this->_clear_compliedfile($tempalte_name);
		# file_put_contents($complied_file, $template_data);
		# 避免高并发下导致的编译文件写不全
		$tmp_file = $complied_file.uniqid('_tpl', true);  
		if (!file_put_contents($tmp_file, $template_data)) err('Err: File "'.$tmp_file.'" can not be generated.');
		$success = @rename($tmp_file, $complied_file);
		if(!$success){
			if(is_file($complied_file)) @unlink($complied_file);
			$success = @rename($tmp_file, $complied_file);
		}
		if(!$success) err('Err: File "'.$complied_file.'" can not be generated.');
		return $complied_file;
	}
	private function _compile_struct($template_data){
		$foreach_inner_before = '<?php $_foreach_$3_counter = 0; $_foreach_$3_total = count($1);?>';
		$foreach_inner_after  = '<?php $_foreach_$3_index = $_foreach_$3_counter;$_foreach_$3_iteration = $_foreach_$3_counter + 1;$_foreach_$3_first = ($_foreach_$3_counter == 0);$_foreach_$3_last = ($_foreach_$3_counter == $_foreach_$3_total-1);$_foreach_$3_counter++;?>';
		$pattern_map = array(
			'<{\*([\s\S]+?)\*}>'      => '<?php /* $1*/?>',
			'(<{((?!}>).)*?)(\$[\w\_\"\'\[\]]+?)\.(\w+)(.*?}>)' => '$1$3[\'$4\']$5',
			'(<{.*?)(\$(\w+)@(index|iteration|first|last|total))+(.*?}>)' => '$1$_foreach_$3_$4$5',
			'<{(\$[\$\w\.\"\'\[\]\:]+?)\snofilter\s*}>'  => '<?php echo $1; ?>',
			'<{(\$[\w\_\"\'\[\]]+?)\s*=(.*?)\s*}>'     => '<?php $1 =$2; ?>', 
			'<{(\$[\$\w\.\"\'\[\]]+?)\s*}>'            => '<?php echo htmlspecialchars($1, ENT_QUOTES, "UTF-8"); ?>', 
			
			'<{if\s*(.+?)}>'          => '<?php if ($1) : ?>',
			'<{else\s*if\s*(.+?)}>'   => '<?php elseif ($1) : ?>',
			'<{else}>'                => '<?php else : ?>',
			'<{break}>'               => '<?php break; ?>',
			'<{continue}>'            => '<?php continue; ?>',
			'<{\/if}>'                => '<?php endif; ?>',
			'<{([A-Z\_]+)}>'            => '<?php echo $1; ?>', 
			'<{foreach\s*(\$[\w\.\_\"\'\[\]]+?)\s*as(\s*)\$([\w\_\"\'\[\]]+?)}>' => $foreach_inner_before.'<?php foreach( $1 as $$3 ) : ?>'.$foreach_inner_after,
			'<{foreach\s*(\$[\w\.\_\"\'\[\]]+?)\s*as\s*(\$[\w\_\"\'\[\]]+?)\s*=>\s*\$([\w\_\"\'\[\]]+?)}>'  => $foreach_inner_before.'<?php foreach( $1 as $2 => $$3 ) : ?>'.$foreach_inner_after,
			'<{\/foreach}>'           => '<?php endforeach; ?>',
			'<{include\s*file=(.+?)}>'=> '<?php include $_view_obj->compile($1); ?>',
		);

		$pattern = $replacement = array();
		foreach($pattern_map as $p => $r){
			$pattern = '/'.str_replace(array("<{", "}>"), array($this->left_delimiter.'\s*','\s*'.$this->right_delimiter), $p).'/i';
			$count = 1;
			while($count != 0){
				$template_data = preg_replace($pattern, $r, $template_data, -1, $count);

			}
		}
		return $template_data;
	}
	private function _compile_function($template_data){
		$pattern = '/'.$this->left_delimiter.'([\w_]+)\s*(.*?)'.$this->right_delimiter.'/';
		return preg_replace_callback($pattern, array($this, '_compile_function_callback'), $template_data);
	}
	private function _compile_function_callback( $matches ){
		if(empty($matches[2]))return '<?php echo '.$matches[1].'();?>';
		if(!strpos($matches[2], '=') && !preg_match('/\s*\$/',$matches[2],$p) && !preg_match('/(^(?:[\'\"]).*?(?:[\'\"])$)/', trim($matches[2]),$p2)) return $matches[0];
		$sysfunc = preg_replace('/\((.*)\)\s*$/', '<?php echo '.$matches[1].'($1);?>', $matches[2], -1, $count);
		if($count)return $sysfunc;
		$pattern_inner = '/\b([\w_]+?)\s*=\s*(\$[\w"\'\]\[\-_>\$]+|"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\')\s*?/'; 
		$params = "";
		if(preg_match_all($pattern_inner, $matches[2], $matches_inner, PREG_SET_ORDER)){
			$params = "[";
			foreach($matches_inner as $m)$params .= '\''. $m[1]."'=>".$m[2].", ";
			$params .= "]";
		}else{
			err('Err: Parameters of \''.$matches[1].'\''. $matches[2].' is incorrect!');
		}
		return '<?php echo '.$matches[1].'('.$params.');?>';
	}
	private function _clear_compliedfile($tempalte_name){
		$dir = scandir($this->compile_dir);
		if($dir){
			$part = md5(realpath($this->template_dir.DS.$tempalte_name));
			foreach($dir as $d){
				if(substr($d, 0, strlen($part)) == $part){
					@unlink($this->compile_dir.DS.$d);
				}
			}
		}
	}
}