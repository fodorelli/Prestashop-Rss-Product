<?php
include(dirname(__FILE__).'/../config/config.inc.php');
require_once(dirname(__FILE__).'/../init.php');

header("Content-type: application/xml");
//Con questa riga mandiamo al browser un header compatibile col formato XML

// Get data
$number = ((int)(Tools::getValue('n')) ? (int)(Tools::getValue('n')) : 10);
$orderBy = Tools::getProductsOrder('by', Tools::getValue('orderby'));
$orderWay = Tools::getProductsOrder('way', Tools::getValue('orderway'));
$id_category = ((int)(Tools::getValue('id_category')) ? (int)(Tools::getValue('id_category')) : Configuration::get('PS_HOME_CATEGORY'));
$products = Product::getProducts((int)Context::getContext()->language->id, 0, ($number > 10 ? 10 : $number), $orderBy, $orderWay, $id_category, true);
$currency = new Currency((int)Context::getContext()->currency->id);
$affiliate = (Tools::getValue('ac') ? '?ac='.(int)(Tools::getValue('ac')) : '');

//Ora iniziamo a occuparci del feed vero e proprio

require_once("feedcreator.class.php"); 
//includiamo la classe col nome che le abbiamo assegnato
    
//e inizializziamo l'oggetto con parametri personalizzati (descrizione, titolo e link)
$rss = new UniversalFeedCreator(); 
$rss->useCached(); 
$rss->title = "shopstampa.com"; 
$rss->description = "Feed del sito shopstampa"; 
$rss->link = "http://www.shopstampa.com";  //Questo non viene reso nel feed, sarà un bug
$rss->feedURL = "http://www.shopstampa.com"; 

//Questa funzione rimpiazza alcuni caratteri speciali con le relative entità XML
//serve per evitare errori nell'output
function xmlentities ( $string ) {
    $ar1 = array ( '&' , '&quot;', '&apos;' , '&lt;' , '&gt;' );
    $ar2 = array ( '&', '"', "’", '<', '>' ) ;
    return str_replace ( $ar1 , $ar2, $string );
}
    
//Questo ciclo che estrae le notizie dal DB e le inserisce come nuovo ITEM nel feed
//I campi da cui estraggo le notizie si chiamano 'subject', 'content', 'cat', e 'pubdate'
//ma nel vostro caso i nomi potrebbero essere differenti, e alcuni campi assenti
//(come Author nel mio caso)
foreach ($products AS $product) {
    $image = Image::getImages((int)($cookie->id_lang), $product['id_product']);
    $imageObj = new Image($image[0]['id_image']);
    
    //Eseguo xhtmlentities() sui primi due campi, che potrebbero contenere entità non valide
//    $data['subject'] = xmlentities($data['subject']);
//    $data['content'] = xmlentities($data['content']);
    //$product->titolo = xmlentities($product->name);
    //$product->description = xmlentities($product->description);
    //$product->category = xmlentities($product->id_category_default);
    //$product->date = xmlentities($articolo->date_add);
   

    //E ora comincio a inserire le informazioni di ogni item.
    $item = new FeedItem(); 
    //notate come a volte prendo i dati così come sono dal db, altre li costruisco al volo
    $item->title = $product['name'];
    $item->link = str_replace('&amp;', '&', htmlspecialchars($link->getproductLink($product['id_product'], $product['link_rewrite'], Category::getLinkRewrite((int)($product['id_category_default']), $cookie->id_lang)))).$affiliate;
    $item->description = "<img src='"._PS_BASE_URL_._THEME_PROD_DIR_.$imageObj->getExistingImgPath()."-large_default.jpg' title='".str_replace('&', '', $product['name'])."' alt='thumb' />" . $product['meta_description'];
  
    
    $item->author = "Shopstampa";
    //$item->category = 
    //La mia PUBDATE è in formato UNIX TIMESTAMP, ma la classe la converte in formato leggibile
//    $item->date = $articolo->datacreazione;
//    $item->date = $data['created'];
    //Questa riga per me è invariabile
   // $item->author = $articolo->autore;

    //Definiamo le opzioni dell'item: questo contiene tag HTML...
    $item->descriptionHtmlSyndicated = true;
    //avremmo impostato FALSE per togliere i tag HTML
    
    //...e contiene anche l'elemento <category>
    $item->categoryHtmlSyndicated = true;

    //decommentando la riga seguente, troncheremmo Description (anche con tag) dopo 500 caratteri
    //item->descriptionTruncSize = 500;

    $rss->addItem($item); //Questo lasciatelo, inserisce il nuovo item coi dati appena processati
} 

//E infine l'output a video.
echo $rss->createFeed("RSS2.0", ""); 
//Ovviamente abbiamo anche la possibilità di salvare il file su disco, o di scegliere altri formati
//Vi rimando ai commenti presenti nella classe per gli esempi del caso.
?>
