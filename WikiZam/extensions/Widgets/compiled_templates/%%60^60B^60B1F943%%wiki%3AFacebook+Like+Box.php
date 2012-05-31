<?php /* Smarty version 2.6.18-dev, created on 2012-05-31 15:46:43
         compiled from wiki:Facebook+Like+Box */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'wiki:Facebook Like Box', 11, false),array('modifier', 'escape', 'wiki:Facebook Like Box', 11, false),array('modifier', 'validate', 'wiki:Facebook Like Box', 11, false),)), $this); ?>


<?php if (isset ( $this->_tpl_vars['height'] )): ?>
<?php elseif ($this->_tpl_vars['faces'] && $this->_tpl_vars['stream']): ?>
<?php $this->assign('height', '556'); ?>
<?php elseif ($this->_tpl_vars['stream']): ?>
<?php $this->assign('height', '395'); ?>
<?php elseif ($this->_tpl_vars['faces']): ?>
<?php $this->assign('height', '258'); ?>
<?php endif; ?>
<iframe src="http://www.facebook.com/plugins/likebox.php?href=<?php echo $this->_tpl_vars['profile']; ?>
&amp;width=<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['width'])) ? $this->_run_mod_handler('default', true, $_tmp, 300) : smarty_modifier_default($_tmp, 300)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')); ?>
&amp;colorscheme=<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['theme'])) ? $this->_run_mod_handler('default', true, $_tmp, 'light') : smarty_modifier_default($_tmp, 'light')))) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')); ?>
&amp;show_faces=<?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['faces'])) ? $this->_run_mod_handler('default', true, $_tmp, false) : smarty_modifier_default($_tmp, false)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')))) ? $this->_run_mod_handler('validate', true, $_tmp, 'boolean') : smarty_modifier_validate($_tmp, 'boolean')); ?>
&amp;stream=<?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['stream'])) ? $this->_run_mod_handler('default', true, $_tmp, false) : smarty_modifier_default($_tmp, false)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')))) ? $this->_run_mod_handler('validate', true, $_tmp, 'boolean') : smarty_modifier_validate($_tmp, 'boolean')); ?>
&amp;header=false&amp;height=<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['height'])) ? $this->_run_mod_handler('default', true, $_tmp, 63) : smarty_modifier_default($_tmp, 63)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')); ?>
<?php if (isset ( $this->_tpl_vars['force_wall'] )): ?>&amp;force_wall=true<?php endif; ?><?php if (isset ( $this->_tpl_vars['border_color'] )): ?>&amp;border_color=<?php echo ((is_array($_tmp=$this->_tpl_vars['border_color'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')); ?>
<?php endif; ?>" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['width'])) ? $this->_run_mod_handler('default', true, $_tmp, 300) : smarty_modifier_default($_tmp, 300)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
px; height:<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['height'])) ? $this->_run_mod_handler('default', true, $_tmp, 63) : smarty_modifier_default($_tmp, 63)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
px; allowTransparency="true" class="<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['float'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, '')))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
">
</iframe>