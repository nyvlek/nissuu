=== nissuu ===
Contributors: Nyvlek
Donate link: http://nyvlek.com/paypal
Tags: issuu,pdf,catalog,shortcode,view,ver,catalogo,publicaciones,revistas
Requires at least: 2.9.2
Tested up to: 3.8.1
Stable tag: trunk

Muestra su catálogo de publicaciones alojadas en Issuu como una entrada en su Wordpress usando un shortcode.

== Description ==

<a href="http://issuu.com" target="_blank">Issuu.com</a> es un gran lugar para alojar sus publicaciones en formato PDF, pero es mejor mantener a sus visitantes en tu sitio
Nyvlek Issuu (nissuu) obtiene (a través de la API de Issuu) una lista de todos sus archivos alojados en issuu.com y permite mostrar esa lista en tu Wordpress a través de un sencillo shortcode.
Puede restringir opcionalmente la lista por etiqueta y controlar sus caracteristicas.

= Uso =
Sólo agregue el shortcode `[nissuu]` donde desea que el catálogo deba mostrarse y añada atributos para personalizarlo.

Ejemplo :
[nissuu tag="" viewer="mini" titlebar="false" vmode="" ctitle="Haz click para ver publicación" height="480" bgcolor="FFFFFF"]

Para personalizar su apariencia , utilice el archivo style.css CSS de su tema.

== Instalación ==

1. Descomprima el archivo zip
2. Copie los archivos en el directorio de su instalación de WordPress "wp-content/plugins"
3. Activar el plugin desde la pestaña "Plugins".
4. Vaya a Configuración Nyvlek Issuu, introduzca su "API key" y el "API secret". 
5. Utilice el código abreviado siempre que le parezca. Para personalizar su apariencia, utilice el archivo style.css CSS de su tema.

= Ejemplo = 
[nissuu tag="" viewer="mini" titlebar="false" vmode="" ctitle="Haz click para ver publicación" height="480" bgcolor="FFFFFF"]

= Opciones =
- **tag=""** :  Si lo desea, puede restringir la lista sólo a pdf con los cordeles provistos. Por defecto: ""
- **viewer="mini"** : Formato de visualización de ISSUU "no", "mini", "presentation" or "window". Por defecto: "mini".
- **titlebar="false"** : Muestra la barra de ISSUU, puedes usar "true", "false". Por defecto: "false".
- **vmode=""** : Muestra las páginas una junto a la otra, o debajo de cada otro ("single"). Puedes usar: "single", "". Por defecto: "".
- **ctitle=""** : Título que debe aparecer en la parte superior de la lista de publicaciones. Por defecto: "Haz click para ver publicación"
- **height="480"** : Controla las dimenciones de alto de la publicación. Por defecto: "480".
- **bgcolor="FFFFFF"** : Muestra el color de fondo del embed en formato hexadecimal. Por defecto :"FFFFFF".
- **img="false"** : Ponga esto en una serie se mostrará la miniatura de cada pdf en el ancho previsto (ex: img="120" se mostrará la miniatura en el ancho de 120px).

== Changelog ==
= 1.0 = 
- Cambio de widget a shortcode

== Screenshots ==
1. Mockup of the Issuu viewer with the list of pdfs underneath, fetched via the Issuu API.
