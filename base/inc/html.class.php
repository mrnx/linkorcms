<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл:			inc/html.class.php
# Назначение:	Класс вывода элементов управления html

class HTML{

	public function FormOpen( $Action = '', $Method = 'post', $Multipart = false, $other = '' ){
		return '<form'
		.($Action != '' ? ' action="'.$Action.'"' : '')
		.($Method != '' ? ' method="'.$Method.'"' : '')
		.($Multipart ? ' enctype="multipart/form-data"' : '')
		.($other != '' ? ' '.$other : '')
		.'>';
	}

	public function FormClose(){
		return '</form>'."\n";
	}

	public function Submit( $caption = 'Submit', $other = '' ){
		return '<input type="submit" value="'.$caption.'" align="middle"'.($other != '' ? ' '.$other : '').">\n";
	}

	public function Button( $caption = 'Button', $other = '' ){
		return '<input type="button" value="'.$caption.'" align="middle"'.($other != '' ? ' '.$other : '').">\n";
	}

	/**
	 * Создает однострочное поле для редактирования текста
	 * @param string $name
	 * @param string $text
	 * @param bool $password
	 * @param string $other
	 * @return string
	 */
	public function Edit( $name, $text = '', $password = false, $other = '' ){
		return '<input type="'.($password ? 'password' : 'text').'" name="'.$name.'"'.($text != '' ? ' value="'.$text.'"' : '').($other != '' ? ' '.$other : '').">\n";
	}

	public function TextArea( $name, $text = '', $other = '' ){
		return '<textarea name="'.$name.'"'.($other != '' ? ' '.$other : '').'>'.$text.'</textarea>'."\n";
	}

	public function Select_open( $name, $multiple = false, $other = '' ){
		return '<select name="'.$name.'"'.($multiple ? ' multiple="multiple"' : '').($other != '' ? ' '.$other : '').">\n";
	}

	public function Option( $name, $caption, $selected = false, $other = '' ){
		return '<option value="'.$name.'"'.($selected ? ' selected="selected"' : '').($other != '' ? ' '.$field['other'] : '').'>'.$caption."</option>\n";
	}

	public function Select_close(){
		return "</select>\n";
	}

	public function Select( $name, $data, $multiple = false, $other = '' ){
		if(!isset($data['selected'])){
			$data['selected'] = '';
		}
		$text = '<select name="'.$name.'"'.($multiple ? ' multiple="multiple"' : '').($other != '' ? ' '.$other : '').">\n";
		foreach($data as $field){
			if(is_array($field)){
				$text .= '<option value="'.$field['name'].'" title="'.$field['caption'].'"'.($field['selected'] || $data['selected'] == $field['name'] ? ' selected="selected"' : '').($field['other'] ? ' '.$field['other'] : '').'>'.$field['caption']."</option>\n";
			}
		}
		$text .= "</select>\n";
		return $text;
	}

	public function DataAdd( &$data, $value, $caption, $selected = false, $other = '' ){
		$data[] = array('name'=>$value, 'caption'=>$caption, 'selected'=>$selected, 'other'=>$other);
	}

	public function Hidden( $name, $value = '', $other = '' ){
		return '<input type="hidden" name="'.$name.'" value="'.$value.'"'.($other != '' ? ' '.$other : '').">\n";
	}

	public function Check( $name, $value, $checked = false, $other = '' ){
		return '<input type="checkbox" name="'.$name.'" value="'.$value.'"'.($checked ? ' checked="checked"' : '').($other != '' ? ' '.$other : '').">\n";
	}

	public function Radio( $name, $value, $checked = false, $other = '' ){
		return '<input type="radio" name="'.$name.'" value="'.$value.'"'.($checked ? ' checked="checked"' : '').($other != '' ? ' '.$other : '').">\n";
	}

	public function FFile( $name, $other = '' ){
		return '<input type="file" name="$name"'.($other ? ' '.$other : '').">\n";
	}

}

?>