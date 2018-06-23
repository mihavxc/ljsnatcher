<?
# Парсим блог на livejournal.com. Часть 2: Получаем контент по ссылкам.
# (с) www.chukhlomin.com

# Настройки
# 0 - не скачивать изображения, используемые в постах. ВНИМАНИЕ, http ссылки на изображения перестанут работать в течении суток.
# 1 - скачивать изображения (это может занять много времени).
$download_img = 1; 
set_time_limit(0);

# Создаем директории для файлов
mkdir('html', 0777, true);
if($download_img==1){mkdir('img', 0777, true);}

# Нужно передать куку, чтобы журнал отображался в стандартном оформлении.
$cookie = array('http'=>array('method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
              "Cookie: prop_opt_readability=1;adult_explicit=1;"));
$context = stream_context_create($cookie);

# для скачивания файлов в случае редиректа
$context_redirect = stream_context_create(
		array('http' => array('follow_location' => true)));

# Функция скачивания изображения
function getimages($html)
	{
		global $t; # счетчик для номер файла.
		global $context_redirect; 

		$html_original=$html;
		$html=str_replace('"',"'",$html);
				
		$pattern = "/<img(.*?)src='(.*?)'(.*?)>/ims";    
		preg_match_all($pattern, $html, $links); 
		
		foreach($links[2] as $link)
			{
				@file_put_contents("img/tmp.jpg", file_get_contents($link,false, $context));
				if(@is_array(getimagesize("img/tmp.jpg")))
					{
						rename("img/tmp.jpg","img/$t.jpg");
						$html_original=str_replace("$link","img/$t.jpg",$html_original);
						$t++;
					}else{
							unlink("img/tmp.jpg");
						}	
			}
		return $html_original;
	}
	
	
# Читаем файл со ссылками на посты
$data=file_get_contents("links.txt");
$data = explode("\n", $data);
$count=count($data)-1;
$i=0;
$t=1;

# Парсим контент по ссылке из файла
while($i<$count)
	{
		$url=trim($data[$i]);
		$post_data=file_get_contents($url, false, $context);
		
		# id
		$pattern = '/.livejournal.com\/(.*?).html/ims';    
		preg_match($pattern, $url, $id);
		$id=$id[1];
	
		# дата
		$pattern = '/<time class=" b-singlepost-author-date published dt-published " >(.*?)<\/time>/ims';    
		preg_match($pattern, $post_data, $date);
		$date=strip_tags($date[1]);
		$date=date("d.m.Y H:i", strtotime($date)); 
			
		# заголовок
		$pattern = '/<meta property="og:title" content="(.*?)" \/>/ims';    
		preg_match($pattern, $post_data, $title);
		$title=$title[1];
		
		# текст
		$pattern = '/<article class=" b-singlepost-body entry-content e-content  " lj-sale-entry lj-discovery-tags lj-embed-resizer >(.*?)<\/article>/ims';    
		preg_match($pattern, $post_data, $text);
		$text=trim(preg_replace('/<div\n    class="lj-like lj-like--v4"(.*?)<\/div>/ims', '', $text[1])); # вырезаем блок с лайками
	
		# теги
		$pattern = '/data-hashtags="(.*?)"/ims';    
		preg_match($pattern, $post_data, $tags);
		$tags=$tags[1];
		
		# комментариев
		$pattern = '/"replycount":(.*?),/ims';  
		preg_match($pattern, $post_data, $comments);	
		$comments=$comments[1];

		# Шаблона для сохранения данных
		$result_html="<html>
		<head>
		<title>$title</title>
		<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
		</head><body>
		<div style='width:50%;padding-left:10px;'>
		<h2>$title</h2>
		$text
		<br><br><hr>
		<small>Дата: $date<br>Теги: $tags<br>Оригинал: <a href='$url'>$url</a></small>
		<br><br>
		<a href='index.html'>Оглавление</a>
		</div>
		</body></html>
		";

		$i++;
		
		# Для построения файла с навигацией
		$index_html.="$i.) <a href='html/$id.html'>$title</a> <small>$date</small><br>";

		if($download_img==1){$result_html=getimages($result_html);}
		
		file_put_contents("html/$id.html", $result_html);
	}	
	
	$now=date('d.m.Y');

	# index файл с навигацией	
	$index_html="<html>
	<head>
	<title>Оглавление блога</title>
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
	</head><body>
	<h2>Оглавление блога</h2>
	<div style='padding-left:10px;'>$index_html</div>
	<br><br><hr>
	<small>Создано $now c помощью <a href='https://github.com/mihavxc/ljsnatcher/'>LJsnatcher</a></small>
	</body></html>";
	

	file_put_contents("index.html", $index_html);
?>
