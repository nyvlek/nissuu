<?php
/*
  Plugin Name: Nissuu
  Plugin URI: http://nyvlek.com/nissuu/
  Description: Muestra su catálogo el publicaciones de Issuu como un post en Wordpress.
  Version: 1.0
  Author: Aris Kelvyn Mota
  Author URI: http://nyvlek.com
 */

if (!class_exists('ap_nissuu')) {

    class ap_nissuu {

        /**
         * @var string The options string name for this plugin
         */
        protected $pluginVersion;
        protected $pluginId;
        protected $pluginPath;
        protected $pluginUrl;
        var $optionsName = 'ap_nissuu_options';
        var $apiKey;
        var $apiSecret;
        var $filterByTag;
        var $cacheDuration;
        var $options = array();
        var $localizationDomain = "ap_nissuu";
        var $url = '';
        var $urlpath = '';

        //Class Functions
        /**
         * PHP 4 Compatible Constructor
         */
        function ap_nissuu() {
            $this->__construct();
        }

        /**
         * PHP 5 Constructor
         */
        function __construct() {
            //Language Setup
            $locale = get_locale();
            $mo = plugin_dir_path(__FILE__) . 'languages/' . $this->localizationDomain . '-' . $locale . '.mo';
            load_textdomain($this->localizationDomain, $mo);

            //"Constants" setup

            $this->url = plugins_url(basename(__FILE__), __FILE__);
            $this->urlpath = plugins_url('', __FILE__);
            //*/
            $this->pluginPath = dirname(__FILE__);
            $this->pluginUrl = WP_PLUGIN_URL . '/' . basename($this->pluginPath);
            $this->pluginVersion = '1.1.0';
            $this->pluginId = 'Nissuu';


            //Initialize the options
            $this->getOptions();
            $this->apiKey = $this->options['ap_nissuu_apikey'];
            $this->apiSecret = $this->options['ap_nissuu_apisecret'];
            $this->cacheDuration = $this->options['ap_nissuu_cacheDuration'];
            $this->no_pdf_message = $this->options['no_pdf_message'];


            //Admin menu
            add_action("admin_menu", array(&$this, "admin_menu_link"));
            add_shortcode('nissuu', array($this, 'shortcode'));
            add_filter('the_posts', array(&$this, 'scripts_and_styles'));
        }

        function listDocs($forceCache = false) {
            require_once('issuuAPI.php');
            $issuuAPI = new issuuAPI(array('apiKey' => $this->apiKey, 'apiSecret' => $this->apiSecret, 'cacheDuration' => $this->cacheDuration, 'forceCache' => $forceCache));
            $result = $issuuAPI->getListing();
            return $result;
        }

        /**
         * @desc Retrieves the plugin options from the database.
         * @return array
         */
        function getOptions() {
            $theOptions = array('ap_nissuu_apikey' => '', 'ap_nissuu_apisecret' => '', 'ap_nissuu_cacheDuration' => 24, 'no_pdf_message' => 'No hay publicaciones');
            $storedOptions = get_option($this->optionsName);

            if (is_array($storedOptions) && count($storedOptions) != count($theOptions)) {
                // Update the options upon plugin updating. Useful if new options have been introduced.
                $storedOptions = array_merge($theOptions, $storedOptions);
                update_option($this->optionsName, $storedOptions);
            }
            if (!is_array($storedOptions)) {
                // this happens on first installation.
                $storedOptions = $theOptions;
            }

            $this->options = $storedOptions;
            $this->cacheDuration = $this->options['ap_nissuu_cacheDuration'];
        }

        function shortcode($atts) {
            ob_start();
            if (!is_admin()) {

                extract(shortcode_atts(array('tag' => '', 'viewer' => 'mini', 'vmode' => '', 'titlebar' => 'false', 'img' => 'false', 'height' => '240', 'bgcolor' => 'FFFFFF', 'ctitle' => 'Pick a PDF file to read'), $atts));

                $this->filterByTag = $tag;

                $docs = $this->listDocs(false);

                if (is_array($docs) && isset($docs['error'])) {
                    echo '<div>' . _("Issuu could not be reached, sorry") . '</div>';
                } else {
                    if ($_GET['documentId'] != '') {
                        $docId = $_GET['documentId'];
                        $docTitle = $_GET['title'];
                    } else {

                        if (count($docs->_content) > 0) {
                            if ($this->filterByTag != '') {
                                foreach ($docs->_content as $d) {
                                    if (in_array($this->filterByTag, $d->document->tags)) {
                                        $docId = $d->document->documentId;
                                        $docTitle = $d->document->title;
                                        break;
                                    }
                                }
                            } else {
                                $docId = $docs->_content[0]->document->documentId;
                                $docTitle = $docs->_content[0]->document->title;
                            }
                        }
                    }
                    $output = '<div id="nissuu">';

                    if (count($docs->_content) > 0) {

                        // display viewer, send it options in array

                        if ($viewer !== 'no') {

                            $output .= $this->issuuViewer(array('documentId' => $docId, 'viewer' => $viewer, 'title' => $docTitle, 'height' => $height, 'bgcolor' => $bgcolor, 'titlebar' => $titlebar, 'vmode' => $vmode));
                        }


                        // loop through the issuus files and display them.
                        $output .= '<h3>' . $ctitle . '</h3>';
                        $output .='<ol class="issuu-list">';
                        $count = 0;
                        foreach ($docs->_content as $d) {


                            $isInTags = (is_array($d->document->tags) && in_array($this->filterByTag, $d->document->tags));
                            $wantItAll = (trim($this->filterByTag) === '');

                            if ($isInTags || $wantItAll) {
                                //$output.=  "want it all = $wantItAll & isInTags=$isInTags";
                                //$output .= "tags =" .print_r($d->document->tags,true);

                                $count++;
                                $issuu_link = 'http://issuu.com/' . $d->document->username . '/docs/' . $d->document->name . '#download';
                                $dId = $d->document->documentId;
                                $doc_link = add_query_arg('documentId', $dId, get_permalink());
                                $doc_link = add_query_arg('title', urlencode($d->document->title), $doc_link);
                                $doc_link.='#nissuu';
                                $selected = ($dId == $docId) ? 'class="issuu-selected"' : '';
                                if ($viewer === 'no') {
                                    $doc_link = $issuu_link;
                                    $link_target = 'target="_blank"';
                                }
                                if ($img != 'false') {
                                    $output .= '<li ' . $selected . '><a class="issuu-view" href="' . $doc_link . '" ' . $link_target . '><img src="http://image.issuu.com/' . $dId . '/jpg/page_1_thumb_medium.jpg" width="' . $img . '">' . $d->document->title . '</a><small>' . $this->formatIssuuDate($d->document->publishDate) . '</small></li>';
                                } else {
                                    $output.= '<li ' . $selected . '><a class="issuu-view" href="' . $doc_link . '" ' . $link_target . '>' . $d->document->title . '<small>' . $this->formatIssuuDate($d->document->publishDate) . '</small></a> </li>';
                                }
                            }
                        }
                        $output.= ($count < 1) ? '<p class="nissuu-no-pdf-message">' . $this->filterByTag . ' ' . $this->no_pdf_message . '</p>' : '';


                        $output.='</ol>
			</div>';

                        echo $output;
                    } else {
                        // No Documents in the json file.
                        echo '<div id="nissuu">' . _("No se han encontrado en su cuenta de Issuu documento") . '</div>';
                    }
                }
            }
            $output_string = ob_get_contents();

            ob_end_clean();

            return $output_string;
        }

        private function formatIssuuDate($date) {
            return date('d M Y', strtotime($date));
        }

        private function issuuViewer($args) {
            $options['documentId'] = $args['documentId'];
            $options['bgcolor'] = $args['bgcolor'];
            $options['mode'] = $args['viewer']; // 'mini', 'Presentation' or 'window'
            $options['height'] = $args['height'];
            $options['title'] = $args['title'];
            $options['titlebar'] = $args['titlebar'];
            $options['vmode'] = ($args['vmode'] == 'single') ? 'singlePage' : '';
            $output = '<h3>' . $options['title'] . '</h3>
			<div id="issuuViewer">
				<object style="width:100%;height:' . $options['height'] . 'px" >
				<param name="movie" value="http://static.issuu.com/webembed/viewers/style1/v2/IssuuReader.swf?mode=' . $options['mode'] . '&amp;backgroundColor=%23' . $options['bgcolor'] . '&amp;viewMode=' . $options['vmode'] . '&amp;embedBackground=%23' . $options['bgcolor'] . '&amp;titleBarEnabled=' . $options['titlebar'] . '&amp;documentId=' . $options['documentId'] . '" />
				<param name="allowfullscreen" value="true"/>
				<param name="menu" value="false"/>
				<param name="wmode" value="transparent"/>
				<embed src="http://static.issuu.com/webembed/viewers/style1/v2/IssuuReader.swf" type="application/x-shockwave-flash" allowfullscreen="true" menu="false" wmode="transparent" style="width:100%;height:' . $options['height'] . 'px" flashvars="mode=' . $options['mode'] . '&amp;backgroundColor=%23' . $options['bgcolor'] . '&amp;viewMode=' . $options['vmode'] . '&amp;embedBackground=%23' . $options['bgcolor'] . '&amp;documentId=' . $options['documentId'] . '&amp;titleBarEnabled=' . $options['titlebar'] . '" />
				</object>
				</div>';


            return $output;
        }

        // ADD JS and CSS IN FRONTEND WHEN RELEVANT

        function scripts_and_styles($posts) {
            if (empty($posts))
                return $posts;
            $shortcode_found = false;

            foreach ($posts as $post) {
                if (stripos($post->post_content, '[nissuu') !== false) {
                    $shortcode_found = true; // bingo!
                    break;
                }
            }

            if ($shortcode_found) {
                // enqueue here
                if (!is_admin()) {
                    $pth_plugin_url = plugin_dir_url(__FILE__);
                    wp_enqueue_style('pixeline_nissuu', $this->pluginUrl . '/' . $this->pluginId . '-frontend.css');
                }
            }

            return $posts;
        }

        /*

          ADMIN STUFF HEREBELOW

         */

        /**
         * Saves the admin options to the database.
         */
        function saveAdminOptions() {
            return update_option($this->optionsName, $this->options);
        }

        /**
         * @desc Adds the options subpanel
         */
        function admin_menu_link() {
            add_options_page('nissuu', 'Nissuu', 10, basename(__FILE__), array(&$this, 'admin_options_page'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2);
        }

        /**
         * @desc Adds the Settings link to the plugin activate/deactivate page
         */
        function filter_plugin_actions($links, $file) {
            $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
            array_unshift($links, $settings_link); // before other links

            return $links;
        }

        /**
         * Adds settings/options page
         */
        function admin_options_page() {

            if ($_POST['ap_nissuu_save']) {
                if (!wp_verify_nonce($_POST['_wpnonce'], 'ap_nissuu-update-options'))
                    die('¡Vaya! Hubo un problema con los datos informados. Por favor, regrese y vuelva a intentarlo.');
                $this->options['ap_nissuu_apikey'] = $_POST['ap_nissuu_apikey'];
                $this->options['ap_nissuu_apisecret'] = $_POST['ap_nissuu_apisecret'];
                $this->options['no_pdf_message'] = $_POST['no_pdf_message'];
                $this->options['ap_nissuu_cacheDuration'] = (int) $_POST['ap_nissuu_cacheDuration'];

                if ($_POST['ap_nissuu_refresh_now'] === '1') {
                    $docs = $this->listDocs(true);
                    if (is_array($docs) && isset($docs['error'])) {
                        $refresh_mess = '<div class="updated"><p>' . _('¡Error! No se puede actualizar: ') . $docs['error'] . '</p><p>(archivo: ' . $issuuAPI->issuuCacheFile . ')</p></div>';
                    } else {
                        $refresh_mess = '<div class="updated"><p>' . _('¡Bien! Cache limpiada con éxito') . '</p></div>';
                    }
                }
                $this->saveAdminOptions();
                echo (empty($refresh_mess)) ? '<div class="updated"><p>' . _('¡Bien! Cache limpiada con éxito') . '</p></div>' : $refresh_mess;
            }
            ?>
            <div class="wrap">
                <h1><?php _e('Nissuu', $this->localizationDomain); ?></h1>
                <p><?php _e('por <a href="http://www.nyvlek.com" target="_blank" class="external">Nyvlek</a>', $this->localizationDomain); ?></p>
                <p style="font-weight:bold;"><?php _e('Si te gusta este plugin, por favor <a href="http://wordpress.org/extend/plugins/nissuu/" target="_blank">danos una puntación</a> en el repositorio de plugins de Wordpress o <a title="Paypal" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HSTMGADNL5PMC">regalame una cerveza o un café</a> :)', $this->localizationDomain); ?></p>

                <h2 style="border-top:1px solid #999;padding-top:1em;"><?php _e('Configuración', $this->localizationDomain); ?></h2>
                <p>
                    <?php _e('Para poder extraer el catálogo de publicaciones desde ISSUU es necesario que proporcione sus credenciales de API. Puede conseguirlas en <a href="https://issuu.com/home/settings/apikey" target="_blank"> aquí</a>.', $this->localizationDomain); ?>
                </p>
                <form method="post" id="ap_nissuu_options">
                    <?php wp_nonce_field('ap_nissuu-update-options'); ?>
                    <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                        <tr valign="top">
                            <th width="33%" scope="row"><?php _e('Api Key:', $this->localizationDomain); ?></th>
                            <td>
                                <input name="ap_nissuu_apikey" type="text" id="ap_nissuu_apikey" size="45" value="<?php echo $this->options['ap_nissuu_apikey']; ?>"/>

                            </td>
                        </tr>
                        <tr valign="top">
                            <th width="33%" scope="row"><?php _e('Api Secret:', $this->localizationDomain); ?></th>
                            <td>
                                <input name="ap_nissuu_apisecret" type="text" id="ap_nissuu_apisecret" size="45" value="<?php echo $this->options['ap_nissuu_apisecret']; ?>"/>

                            </td>
                        </tr>

                        <tr valign="top">
                            <th width="33%" scope="row"><?php _e('Mensaje cuando no hay publicaciones:', $this->localizationDomain); ?></th>
                            <td>
                                <input name="no_pdf_message" type="text" id="no_pdf_message" size="45" value="<?php echo $this->options['no_pdf_message']; ?>"/>

                            </td>
                        </tr>

                        <tr valign="top">
                            <th width="33%" scope="row"><?php _e('Actualizar cada (en minutos):', $this->localizationDomain); ?></th>
                            <td>
                                <input name="ap_nissuu_cacheDuration" type="text" id="ap_nissuu_cacheDuration" size="12" value="<?php echo $this->options['ap_nissuu_cacheDuration']; ?>"/>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th width="33%" scope="row"><?php _e('Limpiar cache:', $this->localizationDomain); ?></th>
                            <td>
                                <label>
                                    <input name="ap_nissuu_refresh_now" type="checkbox" id="ap_nissuu_refresh_now" value="1"/>

                                    <small style="display:inline-block;"><?php _e('Marcar para borrar la cache del servidor.', $this->localizationDomain); ?></small>
                                </label>
                            </td>
                        </tr>

                    </table>
                    <p class="submit">
                        <input type="submit" name="ap_nissuu_save" class="button-primary" value="<?php _e('Guardar Cambios', $this->localizationDomain); ?>" />
                    </p>
                </form>
                <div class="nissuu-block">
                    <h2 style="border-top:1px solid #999;padding-top:1em;">Usage</h2>
                    <h3>Ejemplo:</h3>
                    <code>
                        [nissuu tag="" viewer="mini" titlebar="false" vmode="" ctitle="Haz click para ver publicación" height="480" bgcolor="FFFFFF"]
                    </code>
                    <ul>
                        <li>tag="":  Si lo desea, puede restringir la lista sólo a pdf con los cordeles provistos. Por defecto: ""</li>
                        <li>viewer="mini": Formato de visualización de ISSUU "no", "mini", "presentation" or "window". Por defecto: "mini".</li>
                        <li>titlebar="false": Muestra la barra de ISSUU, puedes usar "true", "false". Por defecto: "false".</li>
                        <li>vmode="": Muestra las páginas una junto a la otra, o debajo de cada otro ("single"). Puedes usar: "single", "". Por defecto: "".</li>
                        <li>ctitle="": Título que debe aparecer en la parte superior de la lista de publicaciones. Por defecto: "Haz click para ver publicación"</li>
                        <li>height="480": Controla las dimenciones de alto de la publicación. Por defecto: "480".</li>
                        <li>bgcolor="FFFFFF": Muestra el color de fondo del embed en formato hexadecimal. Por defecto :"FFFFFF".</li>
                        <li>img="false": Ponga esto en una serie se mostrará la miniatura de cada pdf en el ancho previsto (ex: img="120" se mostrará la miniatura en el ancho de 120px).</li>
                    </ul>
                </div>
                <?php
            }

        }

    }



    if (isset($_GET['ap_nissuu_javascript'])) {
        //embed javascript
        header("content-type: application/x-javascript");
        echo<<<ENDJS
/**
* @desc nissuu
* @author Aris Kelvyn Mota M. - http://nvylek.com
*/

jQuery(document).ready(function(){
	// add your jquery code here


	//validate plugin option form
  	jQuery("#ap_nissuu_options").validate({
		rules: {
			ap_nissuu_apikey: {
				required: true
			},
			ap_nissuu_cacheDuration:{
			required: true,
			min: 60,
			number: true
			}
		},
		messages: {
			ap_nissuu_apikey: {
				// the ap_nissuu_lang object is define using wp_localize_script() in function ap_nissuu_script()
				required: ap_nissuu_lang.required,
				number: ap_nissuu_lang.number,
				min: ap_nissuu_lang.min
			}
		}
	});
});

ENDJS;
    } else {
        if (class_exists('ap_nissuu')) {
            $ap_nissuu_var = new ap_nissuu();
        }
    }
    ?>