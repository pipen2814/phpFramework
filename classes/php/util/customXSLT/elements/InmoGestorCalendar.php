<?php

namespace php\util\customXSLT\elements;

use php\util\customXSLT\elements\base\InmoGestorXSLTElement;

/**
 * Class InmoGestorCalendar
 *
 * Clase del elemento InmoGestorCalendar.
 *
 * @package php
 * @subpackage util
 * @subpackage customXSLT
 * @subpackage elements
 */
class InmoGestorCalendar extends InmoGestorXSLTElement  {

	/**
	 * @var string tag del elemento.
	 */
	protected $tag = 'inmogestor:calendar';

	/**
	 * @var string html del elemento.
	 */
	protected $html = '
		<div>
		<div class=\'input-group date\' id=\'datetimepicker{InmoGestorParam::id}\'>
			<input type=\'text\' class="form-control" id=\'{InmoGestorParam::id}\' name=\'{InmoGestorParam::id}\' 
		value="{InmoGestorParam::value}" />

			<span class="input-group-addon">
				<span class="glyphicon glyphicon-calendar"></span>
			</span>
		</div>

		<script type="text/javascript">
			// http://eonasdan.github.io/bootstrap-datetimepicker/Functions/
			$(function () {
				$(\'#datetimepicker{InmoGestorParam::id}\').datetimepicker({
					format: \'DD-MM-YYYY HH:mm\'
				});
			});
		</script>
		</div>
	';
}
