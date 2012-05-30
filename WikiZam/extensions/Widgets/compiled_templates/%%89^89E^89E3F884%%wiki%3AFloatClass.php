<?php /* Smarty version 2.6.18-dev, created on 2012-05-30 13:56:57
         compiled from wiki:FloatClass */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'wiki:FloatClass', 3, false),)), $this); ?>

<?php if (isset ( $this->_tpl_vars['float'] )): ?>
 class="<?php echo ((is_array($_tmp=$this->_tpl_vars['float'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
"
<?php endif; ?>