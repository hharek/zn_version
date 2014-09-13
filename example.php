<?php
header("Content-type: text/plain");
require "zn_version.php";

try
{
	/* Создание объекта для хранения версий данных (папка должна быть создана) */
	$ver = new ZN_Version("dir", "salt");
	
	/* Создать две версии документов */
	$ver->add("my_doc", "Версия документа 1");
	sleep(1);
	$ver->add("my_doc", "Версия документа 2");
	
	/* Создать две версии страницы */
	$ver->add("my_page", array("Title" => "Заголовок 1", "Content" => "Содержимое 1"));
	sleep(1);
	$ver->add("my_page", array("Title" => "Заголовок 2", "Content" => "Содержимое 2"));
	
	/* Получить версию документа за 2014-09-13 20:08:47 */
	echo $ver->get("my_doc", "2014-09-13 20:08:47");
	
	/* Показать все версии страницы */
	print_r($ver->get("my_page"));
	
	/* Проверить существуют ли версии по документу */
	if($ver->is_set("my_doc"))
	{
		echo "Ура, что то сохранилось";
	}
} 
catch (Exception $e) 
{
	echo $e->getMessage();
}
?>