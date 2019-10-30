<?php
/*
 * formcreate.class.php
 *
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

class Form {

	private $_data;

	public function __construct($data = [])
	{
		$this->_data = $data;
	}

	private function input($type, $name, $label)
	{
		$value = "";
		if (isset($this->_data[$name]))
			$value = $this->_data[$name];
		if ($type == "textarea")
			$input = '<textarea class="form-control" rows="3" id="'.$name.'">'.$value.'</textarea>'.PHP_EOL;
		else
			$input = '<input type="'.$type.'" class="form-control" id="'.$name.'" placeholder="'.$label.'" value="'.$value.'">'.PHP_EOL;
		return '<div class="form-group">'.PHP_EOL.'<label for="'.$name.'">'.$label.'</label>'.PHP_EOL.$input.'</div>'.PHP_EOL;

	}

	public function text($name, $label)
	{
		return $this->input('text', $name, $label);
	}

	public function mail($name, $label)
	{
		return $this->input('mail', $name, $label);
	}

	public function textarea($name, $label)
	{
		return $this->input('textarea', $name, $label);
	}

	public function date($name, $label)
	{
		return '<div class="form-group">
		<h4 for="'.$name.'">'.$label.'</h4>
		<div class="input-group date datepicker">
			<input type="text" id="'.$name.'" class="form-control" data-date-format="DD/MM/YYYY"/>
			<span class="input-group-addon">
				<span class="glyphicon glyphicon-calendar"></span>
			</span>
		</div>
	</div>'.PHP_EOL;
	}


	public function submit($label)
	{
		return '<button type="submit" class="btn btn-primary">'.$label.'</button>'.PHP_EOL;
	}

	public function select($type, $name, $label, $options)
	{
		$option = "";
		foreach ($options as $k => $v)
		{
			$select = '';
			if (isset($this->_data[$name]))
			{
				if ($k == $this->_data[$name])
					$select = ' selected';
			}
			$option .= '<option value="'.$k.'" '.$select.'>'.$v.'</option>'.PHP_EOL;
		}
		$input = '<select id="'.$name.'" class="form-control" '.$type.'>'.PHP_EOL.$option.'</select>'.PHP_EOL;
		return '<div class="form-group">'.PHP_EOL.'<label for="'.$name.'">'.$label.'</label>'.PHP_EOL.$input.'</div>'.PHP_EOL;
	}

	public function checkbox($name, $value, $label, $msg)
	{
		return '<div class="checkbox">'.PHP_EOL.'<h4>'.$label.'</h4><label for="'.$name.'">'.PHP_EOL.'<input type="checkbox" id="'.$name.'" value="'.$value.'">'.PHP_EOL.$msg.'</label><br>'.PHP_EOL;
	}
}
?>
