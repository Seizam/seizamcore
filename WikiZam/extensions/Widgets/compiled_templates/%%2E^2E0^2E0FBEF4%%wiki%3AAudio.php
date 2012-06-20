<?php /* Smarty version 2.6.18-dev, created on 2012-06-20 19:46:02
         compiled from wiki:Audio */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'validate', 'wiki:Audio', 1, false),)), $this); ?>
<audio class="audio<?php if (isset ( $this->_tpl_vars['right'] )): ?> right<?php elseif (isset ( $this->_tpl_vars['left'] )): ?> left<?php endif; ?>" src="<?php echo ((is_array($_tmp=$this->_tpl_vars['url'])) ? $this->_run_mod_handler('validate', true, $_tmp, 'url') : smarty_modifier_validate($_tmp, 'url')); ?>
" controls preload></audio>