<?php

namespace php\util\paginator;


use php\BaseObject;

/**
 * Class PaginatorTemplates
 *
 * Clase en la que agruparemos todos los templates de elementos para el paginador.
 *
 * @package php
 * @subpackage util
 * @subpackage paginator
 */
class PaginatorTemplates extends BaseObject {

	const BUTTON_TEMPLATE = "<input type='button' class='%s' id='%s' name='%s' value='%s' onclick='%s' />";

}