<?php
/**
 * Select field containing a list of templates.
 * Used by Wikiplaces helpers
 */

abstract class HtmlTemplateField extends HTMLFormField {

	/**
	 * @var array
	 */
	protected $templates = array();

	/**
	 * @var string
	 */
	protected $html;

	/**
	 * Constructor
	 *
	 * @param $params array Need a 'templates' key giving the templates list to parse.
	 */
	public function __construct( $params ) {
		parent::__construct( $params );
		$this->selected = null;
	}

	protected function makeTemplates($msg) {
		$levels = array();
		$lines = explode( "\n", $msg );

		foreach ( $lines as $line ) {
			if ( strpos( $line, '*' ) !== 0 ) {
				continue;
			} else {
				list( $level, $line ) = $this->trimStars( $line );

				if ( strpos( $line, '|' ) !== false ) {
					$obj = new HtmlTemplateItem( $line );
					$this->stackItem( $this->templates, $levels, $obj );
				} else {
					if ( $level < count( $levels ) ) {
						$levels = array_slice( $levels, 0, $level );
					}
					if ( $level == count( $levels ) ) {
						$levels[$level - 1] = $line;
					} elseif ( $level > count( $levels ) ) {
						$levels[] = $line;
					}
				}
			}
		}
	}

	/**
	 * @param $str
	 * @return array
	 */
	protected function trimStars( $str ) {
		$numStars = strspn( $str, '*' );
		return array( $numStars, ltrim( substr( $str, $numStars ), ' ' ) );
	}

	/**
	 * @param $list
	 * @param $path
	 * @param $item
	 */
	protected function stackItem( &$list, $path, $item ) {
		$position =& $list;
		if ( $path ) {
			foreach( $path as $key ) {
				$position =& $position[$key];
			}
		}
		$position[] = $item;
	}

	/**
	 * @param $tagset
	 * @param $depth int
	 */
	protected function makeHtml( $tagset, $depth = 0 ) {
		foreach ( $tagset as $key => $val )
			if ( is_array( $val ) ) {
				$this->html .= $this->outputOption(
					$this->internalMsg( $key ), '',
					array(
						'disabled' => 'disabled',
						'style' => 'color: GrayText', // for MSIE
					),
					$depth
				);
				$this->makeHtml( $val, $depth + 1 );
			} else {
				$this->html .= $this->outputOption(
					$this->internalMsg( $val->text ), $val->template,
					array( 'title' => '{{' . $val->template . '}}' ),
					$depth
				);
			}
	}

	/**
	 * @param $text
	 * @param $value
	 * @param $attribs null
	 * @param $depth int
	 * @return string
	 */
	protected function outputOption( $text, $value, $attribs = null, $depth = 0 ) {
		$attribs['value'] = $value;
		if ( $value === $this->selected )
			$attribs['selected'] = 'selected';
		$val = str_repeat( /* &nbsp */ "\xc2\xa0", $depth * 2 ) . $text;
		return str_repeat( "\t", $depth ) . Xml::element( 'option', $attribs, $val ) . "\n";
	}

	protected function internalMsg( $str ) {
		$msg = wfMessage( $str );
		return $msg->exists() ? $msg->text() : $str;
	}

	/**
	 * Accessor for $this->html
	 *
	 * @param $value bool
	 *
	 * @return string
	 */
	public function getInputHTML( $value ) {
		$this->selected = $value;

		$this->html = $this->outputOption( wfMsg( 'notemplate' ), '',
			(bool)$this->selected ? null : array( 'selected' => 'selected' ) );
		$this->makeHtml( $this->templates );

		$attribs = array(
			'name' => $this->mName,
			'id' => $this->mID
		);
		if ( !empty( $this->mParams['disabled'] ) ) {
			$attibs['disabled'] = 'disabled';
		}

		return Html::rawElement( 'select', $attribs, $this->html );
	}
}

class HtmlTemplateItem {
	/**
	 * @var string
	 */
	var $template;

	/**
	 * @var string
	 */
	var $text;

	/**
	 * Constructor
	 *
	 * @param $str String: a line from the template list to parse
	 */
	function __construct( $line ) {
		list( $text, $template ) = explode( '|', strrev( $line ), 2 );

		$this->template = strrev( $template );
		$this->text = strrev( $text );
	}
} 

class WpHomepageTemplate extends HtmlTemplateField {
	
	public function __construct( $params ) {
		parent::__construct($params); 
		$this->makeTemplates( wfMsg( WP_TEMPLATES_FOR_HOMEPAGE ) );
	}
	
}

class WpSubpageTemplate extends HtmlTemplateField {
	
	public function __construct( $params ) {
		parent::__construct($params);
		$this->makeTemplates( wfMsg( WP_TEMPLATES_FOR_SUBPAGE ) );
	}
	
}