<?php /* Smarty version 2.6.18-dev, created on 2012-06-22 19:01:12
         compiled from wiki:YouTube */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'wiki:YouTube', 1, false),array('modifier', 'default', 'wiki:YouTube', 1, false),)), $this); ?>
<iframe class="youtube<?php if (isset ( $this->_tpl_vars['right'] )): ?> right<?php elseif (isset ( $this->_tpl_vars['left'] )): ?> left<?php endif; ?>" width="<?php echo ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['width'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')))) ? $this->_run_mod_handler('default', true, $_tmp, '784') : smarty_modifier_default($_tmp, '784')); ?>
" height="<?php echo ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['height'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')))) ? $this->_run_mod_handler('default', true, $_tmp, '441') : smarty_modifier_default($_tmp, '441')); ?>
" src="http://www.youtube.com/embed/<?php if (isset ( $this->_tpl_vars['playlist'] )): ?>?listType=playlist&list=<?php echo ((is_array($_tmp=$this->_tpl_vars['playlist'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')); ?>
<?php else: ?><?php echo ((is_array($_tmp=$this->_tpl_vars['id'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')); ?>
<?php endif; ?>" frameborder="0" allowfullscreen></iframe>