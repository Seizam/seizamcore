<?php /* Smarty version 2.6.18-dev, created on 2012-06-20 19:42:33
         compiled from wiki:Facebook */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'wiki:Facebook', 1, false),array('modifier', 'escape', 'wiki:Facebook', 1, false),array('modifier', 'validate', 'wiki:Facebook', 1, false),)), $this); ?>
<iframe class="facebook<?php if (isset ( $this->_tpl_vars['right'] )): ?> right<?php elseif (isset ( $this->_tpl_vars['left'] )): ?> left<?php endif; ?>" src="http://www.facebook.com/plugins/likebox.php?href=<?php echo $this->_tpl_vars['profile']; ?>
&amp;width=<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['width'])) ? $this->_run_mod_handler('default', true, $_tmp, 784) : smarty_modifier_default($_tmp, 784)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')); ?>
&amp;height=<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['height'])) ? $this->_run_mod_handler('default', true, $_tmp, 556) : smarty_modifier_default($_tmp, 556)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')); ?>
&amp;colorscheme=light&amp;show_faces=<?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['faces'])) ? $this->_run_mod_handler('default', true, $_tmp, true) : smarty_modifier_default($_tmp, true)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')))) ? $this->_run_mod_handler('validate', true, $_tmp, 'boolean') : smarty_modifier_validate($_tmp, 'boolean')); ?>
&amp;stream=<?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['stream'])) ? $this->_run_mod_handler('default', true, $_tmp, true) : smarty_modifier_default($_tmp, true)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'urlpathinfo') : smarty_modifier_escape($_tmp, 'urlpathinfo')))) ? $this->_run_mod_handler('validate', true, $_tmp, 'boolean') : smarty_modifier_validate($_tmp, 'boolean')); ?>
&amp;header=false<?php if (isset ( $this->_tpl_vars['force_wall'] )): ?>&amp;force_wall=true<?php endif; ?>&amp;border_color=white" scrolling="no" frameborder="0" style="overflow:hidden; width:<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['width'])) ? $this->_run_mod_handler('default', true, $_tmp, 784) : smarty_modifier_default($_tmp, 784)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
px; height:<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['height'])) ? $this->_run_mod_handler('default', true, $_tmp, 556) : smarty_modifier_default($_tmp, 556)))) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
px" allowTransparency="true"></iframe>