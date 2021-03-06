<?php

/**
 * Transactions extension (prefix TM)
 * 
 * @file
 * @ingroup Extensions
 * 
 * @author Clément Dietschy
 * 
 * @license GPL v2 or later
 * @version 0.1.0
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}

/* Setup */
$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'Transactions',
    'author' => array('Clément Dietschy', 'Seizam'),
    'version' => '1.0',
    'url' => 'http://www.seizam.com/',
    'descriptionmsg' => 'tm-desc',
);


$dir = dirname(__FILE__) . '/';

#Load Classes
$wgAutoloadClasses['TransactionsHooks'] = $dir . 'Transactions.hooks.php';
$wgAutoloadClasses['TMRecord'] = $dir . 'model/TMRecord.php';
$wgAutoloadClasses['TMBill'] = $dir . 'model/TMBill.php';
$wgAutoloadClasses['TransactionsTablePager'] = $dir . 'model/TransactionsTablePager.php';


# Attach Hooks
# Adds the necessary tables to the DB
$wgHooks['LoadExtensionSchemaUpdates'][] = 'TransactionsHooks::loadExtensionSchemaUpdates';
# On Electronic Payment action
$wgHooks['CreateTransaction'][] = 'TransactionsHooks::createTransaction';
# On any cancel (if TMR is pending!)
$wgHooks['CancelTransaction'][] = 'TransactionsHooks::cancelTransaction';
# On Electronic Payment action
$wgHooks['ElectronicPaymentAttempt'][] = 'TransactionsHooks::electronicPaymentAttempt';

# i18n
$wgExtensionMessagesFiles['Transactions'] = $dir . 'Transactions.i18n.php';
$wgExtensionAliasesFiles['Transactions'] = $dir . 'Transactions.alias.php';

# Special Transactions
$wgAutoloadClasses['SpecialTransactions'] = $dir . 'SpecialTransactions.php';
$wgSpecialPages['Transactions'] = 'SpecialTransactions';
$wgSpecialPageGroups['Transactions'] = 'users';


# Special Transactions
$wgAutoloadClasses['SpecialBills'] = $dir . 'SpecialBills.php';
$wgSpecialPages['Bills'] = 'SpecialBills';

# Special TransactionsAdmin
$wgAutoloadClasses['SpecialTransactionsAdmin'] = $dir . 'SpecialTransactionsAdmin.php';
$wgSpecialPages['TransactionsAdmin'] = 'SpecialTransactionsAdmin';

#tmr_type for refund
define('TM_REFUND_TYPE', 'refund');


# Right for Transaction administration
define('TM_ACCESS_RIGHT', 'tmaccess');
$wgAvailableRights[] = TM_ACCESS_RIGHT;
$wgGroupPermissions['user'][TM_ACCESS_RIGHT] = true;

# Right for Transaction administration
define('TM_ADMIN_RIGHT', 'tmadmin');
$wgAvailableRights[] = TM_ADMIN_RIGHT;
$wgGroupPermissions['sysop'][TM_ADMIN_RIGHT] = true;