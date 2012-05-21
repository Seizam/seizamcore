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
        $title=$this->getTitle();
        $this->setHeaders();
        self::bigForm($title);
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
            'amount' => array(
                'section' => 'you',
                'label' => 'montant',
                'type' => 'float',
                'default' => 99.99,
                'disabled' => true,
                'help' => 'montant-help'
            ),
            'email' => array(
                'section' => 'you',
                'type' => 'text',
                'default' => 'bob@plop',
                'label' => 'email',
                'help' => 'email-help',
                'disabled' => true,
            ),
            'submit' => array(
                'section' => 'you',
                'type' => 'submit',
                'default' => 'aller à l\'interface de paiement',
            ),
            'password' => array(
                'type' => 'hidden',
                'default' => '<a href="#">Change password</a>'
            ),
            'radio' => array(
                'type' => 'radio',
                'label' => 'who?',
                'options' => array( # The options available within the checkboxes (displayed => value)
                    'Option 0' => 0,
                    'Option 1' => 1,
                    'Option 2' => 3,
                ),
                'default' => 1,
                'help' => 'test-help'
            )
        );

        $htmlForm = new HTMLFormS($formDescriptor, 'sz-profil');

        $htmlForm->setSubmitText(wfMessage('sz-profil-save'));
        $htmlForm->setTitle($title);


        $htmlForm->addHeaderText('Ceci est un texte de tete.');

        $htmlForm->addFooterText('Ceci est un texte de pied.');

        $htmlForm->addHeaderText('Ceci est un texte de tetesection.', 'you');

        $htmlForm->addFooterText('Ceci est un texte de piedsection.', 'you');


        $htmlForm->show();
    }

}