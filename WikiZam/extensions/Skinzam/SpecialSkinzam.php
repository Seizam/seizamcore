<?php

if (!defined('MEDIAWIKI'))
    die();

/**
 * Implements Special:Skinzam
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 * @ingroup Upload
 */

/**
 * Form for handling uploads and special page.
 *
 * @ingroup SpecialPage
 * @ingroup Upload
 */
class SpecialSkinzam extends SpecialPage {

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('Skinzam');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        global $wgOut;
        $this->setHeaders();
        
        $this->getOutput();

        # A formDescriptor Array to tell HTMLForm what to build
        $formDescriptor = array(
            'text' => array(
                'type' => 'text',
                'label' => 'text',
                'default' => 'Valeur par défaut',
                'maxlength'=> 7,
                'help' => 'This is supposed to help'
            ),
            'Lise' => array(
                'type' => 'text',
                'label' => 'Message for Lise',
                'default' => 'What a beautiful girl',
                'maxlength'=> 16,
                'help' => 'That is supposed to be romantic'
            ),
            'password' => array(
                'type' => 'password',
                'label' => 'password',
                'default' => '',
                'maxlength'=> 16,
                'help' => 'This is supposed to help'
            ),
            'float' => array(
                'type' => 'float',
                'label' => 'float',
                'default' => 'plop',
                'maxlength'=> 6,
                'min' => 41,
                'max' => 43,
                'help' => 'This is supposed to help'
            ),
            'int' => array(
                'type' => 'int',
                'label' => 'int',
                'default' => 1789,
                'maxlength'=> 4,
                'min' => 0,
                'max' => 2011,
                'help' => 'This is supposed to help'
            ),
            'textarea' => array(
                'type' => 'textarea',
                'label' => 'textarea',
                'default' => 'Valeur par défaut',
                'rows' => 3,
                'help' => 'This is supposed to help'
            ),
            'select' => array(
                'type' => 'select',
                'label' => 'select',
                'options' => array(
                    'Option 0' => 0,
                    'Option 1' => 1,
                    'Option 2' => 'option2id'
                ),
                'help' => 'This is supposed to help'
            ),
            'selectorother' => array(
                'type' => 'selectorother',
                'label' => 'selectorother',
                'options' => array(
                    'Option 0' => 0,
                    'Option 1' => 1,
                    'Option 2' => 'option2id'
                ),
                'maxlength'=> 10,
                'help' => 'This is supposed to help'
            ),
            'selectandother' => array(
                'type' => 'selectandother',
                'label' => 'selectandother',
                'options' => array(
                    'Option 0' => 0,
                    'Option 1' => 1,
                    'Option 2' => 'option2id'
                ),
                'maxlength'=> 10,
                'help' => 'This is supposed to help'
            ),
            'multiselect' => array(
                'type' => 'multiselect',
                'label' => 'multiselect',
                'options' => array(
                    'Option 0' => 0,
                    'Option 1' => 1,
                    'Option 2' => 'option2id'
                ),
                'default' => array(0, 'option2id'),
                'help' => 'This is supposed to help'
            ),
            'radio' => array(
                'type' => 'radio',
                'label' => 'radio',
                'options' => array(
                    'Option 0 Option 0 Option 0 Option 0 Option 0 Option 0 Option 0 Option 0 Option 0 Option 0 Option 0 Option 0' => 0,
                    'Option 1' => 1,
                    'Option 2' => 'option2id'
                ),
                'default' => 1,
                'help' => 'This is supposed to help'
            ),
            'check' => array(
                'type' => 'check',
                'label' => 'check check check check check check check check check check check check check check check check check check check check check check check check check check check check check check check',
                'help' => 'This is supposed to help'
            ),
            'info' => array(
                'section' => 'section',
                'type' => 'info',
                'label' => 'info',
                'default' => '<a href="http://www.davidcanwin.com">DavidCanWin.com</a> <a href="http://www.davidcanwin.com">DavidCanWin.com</a> <a href="http://www.davidcanwin.com">DavidCanWin.com</a> <a href="http://www.davidcanwin.com">DavidCanWin.com</a> <a href="http://www.davidcanwin.com">DavidCanWin.com</a>',
                'raw' => true,
                'help' => 'This is supposed to help'
            ),
            'submit' => array(
                'type' => 'submit',
                'help' => 'This is supposed to help'
            ),
            'hidden' => array(
                'type' => 'hidden',
                'label' => 'hidden',
                'default' => 'This Intel Is Hidden',
                'help' => 'This is supposed to help'
            )
);
        $htmlForm = new HTMLFormS($formDescriptor, 'myform'); # We build the HTMLForm object, calling the form "myform"

        $htmlForm->setSubmitText(wfMessage('myform-submit')); # What text does the submit button display
        $htmlForm->setTitle($this->getTitle()); # You must call setTitle() on an HTMLForm

        $htmlForm->setSubmitCallback(array('SpecialSkinzam', 'processInput'));
        
        $htmlForm->addHeaderText('ceci est un text de tete','section');

        $htmlForm->show(); # Displaying the form
    }

    static function processInput($formData) {
            return true;
    }

    static function validateSimpleTextField($simpleTextField, $allData) {
        if ($simpleTextField == 'merde?!?') {
            return 'Excuse my French';
        }
        return true;
    }

    static function filterSimpleTextField($simpleTextField, $allData) {
        return $simpleTextField . "?!?";
    }

    # Just an array print fonction

    static function sayIt($in) {
        global $wgOut;
        $wgOut->addHTML('<pre>');
        $wgOut->addHTML(print_r($in, true));
        $wgOut->addHTML('</pre>');
    }

    static function bigBlob($title) {
        return '<div class="informations">Ceci est un message neutre</div>
							<div class="informations error">Ceci est un message d\'erreur</div>
							<div class="informations success">Ceci est un message de succès</div>
							
							<div class="edit_col_1">
								<fieldset>
									<legend>Vous</legend>
									<div class="content_block">
										<p>
											<span class="label_like">Nom d\'utilisateur&nbsp;:</span>
											<span class="input_like">bisounours67</span>
										</p>
										<p>
											<label>Nom réel&nbsp;:</label>
											<input type="text" name="name" id="name" value="Jean-Pierre Cormoran" />
											<span class="sread help">Inscrivez vos noms et prénoms dans ce champs. Ces informations ne sont pas publiques.</span>
										</p>
										<p>
											<label for="gender">Sexe&nbsp;:</label>
											<select name="gender" id="gender">
												<option value="n">Non renseigné</option>
												<option value="m">Masculin</option>
												<option value="f">Féminin</option>
											</select>
											<span class="sread help">Renseignez votre genre. Cette information n\'est pas publique.</span>
										</p>
									</div>
								</fieldset>
								
								<fieldset>
									<legend>Le compte</legend>
									<div class="content_block">
										<p>
											<span class="label_like">Mot de passe&nbsp;:</span>
											<span class="input_like"><a href="#" class="important">Changer de mot de passe</a></span>
											<span class="sread help">La procédure de changement de mot de passe vous demande à nouveau votre mot de passe pour vérifier votre identité avant tout changement. Il vous sera ensuite demandé d\'entrer deux fois votre nouveau mot de passe.</span>
										</p>
										<p>
											<span class="label_like">Se souvenir de moi pendant 30 jours&nbsp;:</span>
                                                                                        <span class="input_like">
                                                                                            <span><input type="radio" name="remember" id="yes" checked="checked" /> <label for="yes">Oui</label></span>	
                                                                                            <span><input type="radio" name="remember" id="no" checked="checked" /> <label for="no">Non</label></span>	
                                                                                            <span><input type="radio" name="remember" id="dunno" checked="checked" /> <label for="dunno">Je sais pas</label></span>	
                                                                                        </span>											
                                                                                        <span class="sread help">Vous permet de rester connecter à votre espace. Renseignez "non" si vous naviguez sur un poste public ou si vous souhaitez optimiser la sécurité de votre compte Seizam.</span>
										</p>
										<p>
											<label for="lang">Langue&nbsp;:</label>
											<select name="lang" id="lang">
												<option value="fr_FR">Français</option>
												<option value="en_US" lang="en">English</option>
												<option value="de_DE" lang="de">Deutsch</option>
											</select>
											<span class="sread help">Renseignez votre genre. Cette information n\'est pas publique.</span>
										</p>
									</div>
								</fieldset>
								
								<fieldset>
									<legend>En bonus</legend>
									<div class="content_block">
										<p>
											<label for="signature">Signature&nbsp;:</label>
											<textarea name="signature" id="signature" cols="55" rows="8"></textarea>
											<span class="sread help">Vous permet de rester connecter à votre espace. Renseignez "non" si vous naviguez sur un poste public ou si vous souhaitez optimiser la sécurité de votre compte Seizam.</span>
										</p>
										<p class="radio_line">
											<span class="label_like">Me voir publiquement&nbsp;:</span>
											<input type="radio" name="visible" id="yes_v" checked="checked" /> <label for="yes_v">Oui</label>
											<input type="radio" name="visible" id="no_v" checked="checked" /> <label for="no_v">Non</label>
											<span class="sread help">Permet d\'afficher ou de masquer votre profil lors de recherches sur Seizam ou pour les moteurs de recherche.</span>
										</p>
									</div>
								</fieldset>
								
								<p class="submit">
									<label class="sread" for="save">Sauvegarder les modifications sur mon profil</label>
									<input type="submit" value="Sauvegarder" name="save" id="save" />
								</p>
							</div>
							<div class="edit_col_2">
								<div id="help_zone" class="content_block">
									<h4>Besoin d’aide ? </h4>
									<p>Ce bloc affichera une aide contextuelle pour le remplissage de ce formulaire.<br /> Il vous suffit de passer sur le "?" à côté d\'un champ pour obtenir de l\'aide ou des précisions.</p>
								</div>
							</div>
						</div>
					</form>
				</div>';
    }

    static function bigForm($title) {
        global $wgUser;
        $formDescriptor = array(
            'username' => array(
                'section' => 'you',
                'label-message' => 'username',
                'type' => 'info',
                'default' => $wgUser->getName(),
                'disabled' => true,
                'help-message' => 'prefs-help-realname'
            ),
            'realname' => array(
                'section' => 'you',
                'class' => 'HTMLTextField',
                'default' => $wgUser->getRealName(),
                'label-message' => 'yourrealname',
                'help-message' => 'prefs-help-realname',
                'required' => true,
            ),
            'gender' => array(
                'section' => 'you',
                'class' => 'HTMLSelectField',
                'options' => array(
                    wfMsg('gender-male') => 'male',
                    wfMsg('gender-female') => 'female',
                    wfMsg('gender-unknown') => 'unknown',
                ),
                'label-message' => 'yourgender',
                'help-message' => 'prefs-help-gender',
            ),
            'password' => array(
                'section' => 'account',
                'type' => 'info',
                'label-message' => 'yourpassword',
                'default' => '<a href="#">Change password</a>',
                'raw' => true,
                'help-message' => 'prefs-resetpass'
            ),
            'rememberme' => array(
                'section' => 'account',
                'class' => 'HTMLRadioField',
                'label' => 'Remember me for 30 days :',
                'help' => 'Select Yes to avoid logging in for 30 days',
                'options' => array(
                    'Yes' => 'yes',
                    'No' => 'no',
                ),
            ),
            'lang' => array(
                'section' => 'account',
                'class' => 'HTMLSelectField',
                'label' => 'Language :',
                'options' => array('French' => 'french', 'English' => 'english'),
                'help' => 'Interface Language'
            ),
            'signature' => array(
                'section' => 'bonus',
                'type' => 'textarea',
                'rows' => 2,
                'label' => 'Signature :',
                'help' => 'This text is added when typing "~~~~" in Wikitext'
            ),
            'public' => array(
                'section' => 'bonus',
                'type' => 'multiselect',
                'label' => 'See me publicly :',
                'options' => array(
                    'Yes' => 'yes',
                    'No' => 'no',
                    'I don\'t know' => 'na'
                ),
                'default' => array('na'),
                'help' => 'Can people find you through Seizam.com'
            ),
        );

        $htmlForm = new HTMLFormS($formDescriptor, 'sz-profil');

        $htmlForm->setSubmitText(wfMessage('sz-profil-save'));
        $htmlForm->setTitle($title);

        $htmlForm->show();
    }

}