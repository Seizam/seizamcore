<?php /* Smarty version 2.6.18-dev, created on 2012-06-13 21:59:14
         compiled from wiki:Dailymotion */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'wiki:Dailymotion', 1, false),array('modifier', 'escape', 'wiki:Dailymotion', 1, false),)), $this); ?>
<iframe class="vimeo<?php if (isset ( $this->_tpl_vars['right'] )): ?> right<?php elseif (isset ( $this->_tpl_vars['left'] )): ?> left<?php endif; ?>" frameborder="0" width="<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['width'])) ? $this->_run_mod_handler('default', true, $_tmp, 784) : smarty_modifier_default($_tmp, 784)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
" height="<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['height'])) ? $this->_run_mod_handler('default', true, $_tmp, 441) : smarty_modifier_default($_tmp, 441)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
" src="http://www.dailymotion.com/embed/video/<?php echo ((is_array($_tmp=$this->_tpl_vars['id'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')); ?>
"></iframe>