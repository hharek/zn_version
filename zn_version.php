<?php
/**
 * Версии данных
 */
class ZN_Version
{
	/**
	 * Папка для хранения файлов версий
	 * 
	 * @var string
	 */
	private $_dir;

	/**
	 * Соль для наименования файлов
	 * 
	 * @var string
	 */
	private $_salt;
	
	/**
	 * Формат даты
	 * 
	 * @var string
	 */
	private $_date_format = "Y-m-d H:i:s";

	/**
	 * Максимальное кол-во версий на объект
	 *  
	 * @var type 
	 */
	private $_count = 10;

	/**
	 * Конструктор
	 * 
	 * @param string $dir
	 */
	public function __construct($dir, $salt)
	{
		/* Папка */
		if(empty($dir))
		{
			throw new Exception("Папка для хранения файлов не указана");
		}
		
		$dir = realpath($dir);
		
		if(!is_dir($dir))
		{
			throw new Exception("Папки «{$dir}» не существует.");
		}
		
		$this->_dir = $dir;
		
		/* Соль */
		if(!is_string($salt))
		{
			throw new Exception("Соль указана неверно. Не является строкой.");
		}
		
		if(mb_strlen($salt) === 0)
		{
			throw new Exception("Соль указана неверно. Пустая строка.");
		}
		
		if(mb_strlen($salt) > 16)
		{
			throw new Exception("Соль не должна превыщать 16 символов.");
		}
		$this->_salt = $salt;
	}
	
	/**
	 * Существует ли
	 * 
	 * @param string $identified
	 */
	public function is_set($identified)
	{
		$this->_check_identified($identified);
		$file = $this->_get_file_name($identified);
		
		return is_file($file);
	}
	
	/**
	 * Проверка на существование
	 * 
	 * @param string $identified
	 * @param string $date
	 */
	public function exist($identified, $date = null)
	{
		if(!$this->is_set($identified))
		{
			throw new Exception("Версий для «{$identified}» не существует.");
		}
		
		if(!is_null($date))
		{
			$this->_check_date($date);
			$data_file = unserialize(file_get_contents($this->_get_file_name($identified)));
			if(!isset($data_file[$this->_convert_date($date)]))
			{
				throw new Exception("Версии для «{$identified}» на дату «{$date}» не существует.");
			}
		}
	}
	
	/**
	 * Получить данные
	 * 
	 * @param string $identified
	 * @param string $date
	 * @return mixed
	 */
	public function get($identified, $date = null)
	{
		/* Проверка */
		$this->exist($identified, $date);
		
		/* Все версии */
		$data_file = unserialize(file_get_contents($this->_get_file_name($identified)));
		if(is_null($date))
		{
			return $data_file;
		}
		
		/* Версия по дате */
		return $data_file[$this->_convert_date($date)];
	}
	
	/**
	 * Добавить
	 * 
	 * @param string $identified
	 * @param mixed $data
	 */
	public function add($identified, $data)
	{
		/* Проверка */
		self::_check_identified($identified);
		if(func_num_args() !== 2)
		{
			throw new Exception("Данные не указаны");
		}
		
		/* Существующие версии */
		$data_file = array();
		if($this->is_set($identified) === true)
		{
			$data_file = $this->get($identified);
		}
		
		/* Последний элемент */
		$last = end($data_file);
		if(serialize($last) === serialize($data))
		{
			return;
		}
		
		/* Максимальное кол-во */
		if(count($data_file) === $this->_count)
		{
			array_shift($data_file);
		}
		
		/* Добавить */
		$data_file[date($this->_date_format)] = $data;
		file_put_contents($this->_get_file_name($identified), serialize($data_file));
	}
	
	/**
	 * Назначить данные за определённую дату
	 * 
	 * @param string $identified
	 * @param string $date
	 * @param mixed $data
	 */
	public function edit($identified, $date, $data)
	{
		/* Проверка */
		self::exist($identified, $date);
		if(func_num_args() !== 3)
		{
			throw new Exception("Данные не указаны.");
		}
		
		/* Редактировать */
		$data_file = $this->get($identified);
		$data_file[$date] = $data;
		
		/* Заполнить новые данные */
		file_put_contents($this->_get_file_name($identified), serialize($data_file));
	}
	
	/**
	 * Удалить
	 * 
	 * @param string $identified
	 * @param string $date
	 */
	public function delete($identified, $date = null)
	{
		/* Проверка */
		self::exist($identified, $date);
		
		/* Удалить полностью */
		if(is_null($date))
		{
			unlink($this->_get_file_name($identified));
			return;
		}
		
		/* Поиск нужной даты */
		$data_file = $this->get($identified);
		$date = date($this->_date_format, strtotime($date));
		if(isset($data_file[$date]) === false)
		{
			throw new Exception("Версии для «{$identified}» на дату «{$date}» не существует.");
		}
		
		/* Редактировать */
		unset($data_file[$date]);
		
		/* Заполнить новые данные */
		file_put_contents($this->_get_file_name($identified), serialize($data_file));
	}
	
	/**
	 * Проверка
	 * 
	 * @param string $identified
	 */
	private function _check_identified($identified)
	{
		/* Идентификатор */
		if(!is_string($identified))
		{
			throw new Exception("Идентификатор задан неверно. Не является строкой.");
		}
		
		$result = strpbrk($identified, "\n\r\t\v\f\0");
		if ($result !== false)
		{
			throw new Exception("Идентификатор задан неверно. Недопустимые символы.");
		}

		if (mb_strlen($identified) > 255)
		{
			throw new Exception("Идентификатор задан неверно. Большая строка.");
		}
	}
	
	/**
	 * Проверить дату
	 * 
	 * @param string $date
	 */
	private function _check_date($date)
	{
		if(!is_null($date))
		{
			if(strtotime($date) === false)
			{
				throw new Exception("Дата указана неверно.");
			}
		}
	}

	/**
	 * Получить имя файла
	 * 
	 * @param string $identified
	 * @return string
	 */
	private function _get_file_name($identified)
	{
		return $this->_dir . "/" . md5($this->_salt . $identified);
	}
	
	/**
	 * Преобразовать дату
	 * 
	 * @param string $date
	 * @return string
	 */
	private function _convert_date($date)
	{
		return date($this->_date_format, strtotime($date));
	}
}
?>