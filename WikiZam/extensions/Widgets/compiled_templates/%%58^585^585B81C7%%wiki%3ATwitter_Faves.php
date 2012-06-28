<?php /* Smarty version 2.6.18-dev, created on 2012-06-28 15:42:14
         compiled from wiki:Twitter_Faves */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'counter', 'wiki:Twitter_Faves', 1, false),array('modifier', 'escape', 'wiki:Twitter_Faves', 6, false),array('modifier', 'default', 'wiki:Twitter_Faves', 6, false),array('modifier', 'validate', 'wiki:Twitter_Faves', 10, false),)), $this); ?>
<div class="twitter<?php if (isset ( $this->_tpl_vars['right'] )): ?> right<?php elseif (isset ( $this->_tpl_vars['left'] )): ?> left<?php endif; ?>"><?php echo smarty_function_counter(array('name' => 'twittercounter','assign' => 'twitterincluded'), $this);?>
<?php if ($this->_tpl_vars['twitterincluded'] == 1): ?><script charset="utf-8" src="http://widgets.twimg.com/j/2/widget.js"></script><?php endif; ?>
<script>
new TWTR.Widget({
  version: 2,
  type: 'faves',
  rpp: '<?php echo ((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['count'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'quotes') : smarty_modifier_escape($_tmp, 'quotes')))) ? $this->_run_mod_handler('default', true, $_tmp, 5) : smarty_modifier_default($_tmp, 5)); ?>
',
  interval: 6000,
  title: '<?php echo ((is_array($_tmp=$this->_tpl_vars['title'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'quotes') : smarty_modifier_escape($_tmp, 'quotes')); ?>
',
  subject: '<?php echo ((is_array($_tmp=$this->_tpl_vars['subject'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'quotes') : smarty_modifier_escape($_tmp, 'quotes')); ?>
',
  width: <?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['width'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'quotes') : smarty_modifier_escape($_tmp, 'quotes')))) ? $this->_run_mod_handler('default', true, $_tmp, 784) : smarty_modifier_default($_tmp, 784)))) ? $this->_run_mod_handler('validate', true, $_tmp, 'int') : smarty_modifier_validate($_tmp, 'int')); ?>
,
  height: <?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['height'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'quotes') : smarty_modifier_escape($_tmp, 'quotes')))) ? $this->_run_mod_handler('default', true, $_tmp, 441) : smarty_modifier_default($_tmp, 441)))) ? $this->_run_mod_handler('validate', true, $_tmp, 'int') : smarty_modifier_validate($_tmp, 'int')); ?>
,
  theme: {
    shell: {
      background: '#dad9d9',
      color: '#ffffff'
    },
    tweets: {
      background: '#fcfcfc',
      color: '#4d4e4f',
      links: '#e22c2e'
    }
  },
  features: {
    scrollbar: <?php if (isset ( $this->_tpl_vars['scrollbar'] )): ?>true<?php else: ?>false<?php endif; ?>,
    loop: <?php if (isset ( $this->_tpl_vars['loop'] )): ?>true<?php else: ?>false<?php endif; ?>,
    live: <?php if (isset ( $this->_tpl_vars['live'] )): ?>true<?php else: ?>false<?php endif; ?>,
    behavior: '<?php if (isset ( $this->_tpl_vars['all'] )): ?>all<?php else: ?>default<?php endif; ?>'
  }
}).render().setUser('<?php echo ((is_array($_tmp=$this->_tpl_vars['user'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'quotes') : smarty_modifier_escape($_tmp, 'quotes')); ?>
').start();
</script>
</div>