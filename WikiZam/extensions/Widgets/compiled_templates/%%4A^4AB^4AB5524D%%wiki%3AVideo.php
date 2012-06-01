<?php /* Smarty version 2.6.18-dev, created on 2012-06-01 11:44:04
         compiled from wiki:Video */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'wiki:Video', 3, false),array('modifier', 'escape', 'wiki:Video', 3, false),array('modifier', 'validate', 'wiki:Video', 3, false),)), $this); ?>

<script src="http://api.html5media.info/1.1.5/html5media.min.js"></script>
<video class="<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['float'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, '')))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
" src="<?php echo ((is_array($_tmp=$this->_tpl_vars['url'])) ? $this->_run_mod_handler('validate', true, $_tmp, 'url') : smarty_modifier_validate($_tmp, 'url')); ?>
" width="<?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['width'])) ? $this->_run_mod_handler('default', true, $_tmp, 'auto') : smarty_modifier_default($_tmp, 'auto')))) ? $this->_run_mod_handler('validate', true, $_tmp, 'int') : smarty_modifier_validate($_tmp, 'int')))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
" height="<?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['height'])) ? $this->_run_mod_handler('default', true, $_tmp, 'auto') : smarty_modifier_default($_tmp, 'auto')))) ? $this->_run_mod_handler('validate', true, $_tmp, 'int') : smarty_modifier_validate($_tmp, 'int')))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
" controls preload></video>