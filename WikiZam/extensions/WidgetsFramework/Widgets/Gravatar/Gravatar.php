<?php

namespace WidgetsFramework;

class Gravatar extends ParserFunction {
	
	/** @var XorParameter */
	protected $source;
	/** @var IntegerInPixel */
	protected $size;
	/** @var XorParameter */
	protected $rating;
	/** @var Option */
    protected $right;
    /** @var Option */
    protected $left;

	protected function declareParameters() {
		
		$this->source = new XorParameter('source');
				
		$email = new String('email');
		$email->setValidateType('email');
		$this->source->addParameter($email);
		
		$user = new User('user');
		$this->source->addParameter($user);
		
		global $wgUser;
		$this->source->setDefaultValue($wgUser, false);
		$this->addParameter($this->source);
				
		
		$this->size = new IntegerInPixel('size');
		$this->size->setDefaultValue(80);
		$this->addParameter($this->size);

		$this->rating = new XorParameter('rating');
		$this->rating->addParameter(new Option('g')); // +++ all websites with any audience type
		$this->rating->addParameter(new Option('pg')); // ++ 
		$this->rating->addParameter(new Option('r')); // + 
		$this->rating->addParameter(new Option('x')); // ! hardcore
		$this->addParameter($this->rating);
		
		$float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
		
	}

	/**
     * 
     * @return string
     */
    protected function getClass() {
     
        if ($this->right->getValue()) {
            return 'class="wfmk_right" ';
        } elseif ($this->left->getValue()) {
            return 'class="wfmk_left" ';
        } else {
            return '';
        }
    }
	
	protected function getOutput() {
		
		$this->setBlock(false);
		
		$email = $force_default = $a_open = $a_close = '';
		
		$source = $this->source->getValue(); // type String or \User

		if ($source instanceof \User) { // parameter 'user'
			$email = $source->getEmail();
		} elseif (is_string($source)) { // parameter 'email'
			$email = $source;
		} else {
			$force_default = '&f=y';
		}	

		$email = md5( strtolower( trim( $email ) ) );
		
		$size = 's=' . $this->size->getOutput();
		
		$rating = $this->rating->hasBeenSet() ? '&r='.$this->rating->getOutput() : '';
				
		return $a_open . 
				'<img '.
					$this->getClass() . 
					' src="http://www.gravatar.com/avatar/' . $email . '?' . 
						$size . $rating . $force_default . '&d=mm" alt="" />' .
				$a_close ;
		
	}
	
}