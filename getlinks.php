<?
# Парсим блог на livejournal.com. Часть 1: Получаем ссылки на все посты.
# (с) www.chukhlomin.com

# Настройки
$lj_nick='tema'; # чей блог парсим?
set_time_limit(0);


# Нужно передать куку, чтобы журнал отображался в стандартном оформлении.
$cookie = array('http'=>array('method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
              "Cookie: prop_opt_readability=1;adult_explicit=1;"));
$context = stream_context_create($cookie);

# Определяем года активности блогера.
$data=file_get_contents("https://$lj_nick.livejournal.com/calendar/", false, $context);
$pattern = "/<li class=\"j-nav-item j-years-nav-item(.*?)<\/li>/ims";    
	preg_match_all($pattern, $data, $years); 
	
# Начинаем перебирать годы и месяцы, чтобы собирать ссылки на все посты.
foreach($years[0] as $year) 
	{
	$year=strip_tags($year);	
	$i=1;
	while($i<13)
		{
			if(strlen($i)==1){$month="0$i";}else{$month=$i;}
			$month_data=file_get_contents("https://$lj_nick.livejournal.com/$year/$month/", false, $context);

			$pattern = '/<span class="e-time">(.*?)<\/span>  <a href="(.*?)" class="j-day-subject-link">(.*?)<\/a>/ims';    
			preg_match_all($pattern, $month_data, $links); 
			foreach($links[2] as $link) 
				$result.=trim($link)."\r\n";
			$i++;
		}
	}

# Сохраняем все ссылки в файл.
file_put_contents('links.txt', $result);
?>

