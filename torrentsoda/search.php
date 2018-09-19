<?php
/********************************************************************************\
| MIT License                                                                    |
|                                                                                |
| Copyright (c) 2018 isul@isulnara.com                                           |
|--------------------------------------------------------------------------------|
| Permission is hereby granted, free of charge, to any person obtaining a copy   |
| of this software and associated documentation files (the "Software"), to deal  |
| in the Software without restriction, including without limitation the rights   |
| to use, copy, modify, merge, publish, distribute, sublicense, and/or sell      |
| copies of the Software, and to permit persons to whom the Software is          |
| furnished to do so, subject to the following conditions:                       |
|                                                                                |
| The above copyright notice and this permission notice shall be included in all |
| copies or substantial portions of the Software.                                |
|                                                                                |
| THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR     |
| IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,       |
| FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE    |
| AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER         |
| LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,  |
| OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE  |
| SOFTWARE.                                                                      |
\********************************************************************************/
?>
<?php
include('simple_html_dom.php');

class SynoDLMSearchTorrentSoda
{
	private $qurl = "https://torrentsoda.com/?s=";
	
	private $debugEnabled = false;


	public function __construct()
	{
	}


	public function prepare($curl, $query)
	{
		$url = $this->qurl . urlencode($query);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
		$response = curl_exec($curl);
		return $response;
	}

	
	public function parse($plugin, $response)
	{
		$dom = str_get_html($response);
		$main = $dom->find('main', 0);
		$articles = $main->find('article');
		$count = 0;
		foreach ($articles as $article)
		{
			$entry = explode('의', $article->find('p', 0)->innertext);
			$category = $entry[0];
			$list = $article->find('li');
			foreach ($list as $row)
			{
				$a = $row->find(a);
				$title = $a[0]->innertext;
				$info = explode('||', $a[1]->href);
				$download = (count($info)==1) ? $info[0] : $info[1];
				if (empty($title) || empty($download))
					continue;
				$title = "[$category] $title";
				$size = (count($info)==1) ? 0 : str_replace(",", "", $info[2]);
				if (preg_match_all("/([0-9\.]+)(K|M|G|T)/siU", $size, $matches, PREG_SET_ORDER))
				{
					$size = $matches[0][1];
					switch ($matches[0][2])
					{
						 case 'K':
								 $size = $size * 1024;
								 break;
						 case 'M':
								 $size = $size * 1024 * 1024;
								 break;
						 case 'G':
								 $size = $size * 1024 * 1024 * 1024;
								 break;
						 case 'T':
								 $size = $size * 1024 * 1024 * 1024 * 1024;
								 break;
					}
					$size = floor($size);
				}				
				$datetime = date('Y-m-d H:i:s', strtotime("+$count seconds"));
				$page = "1";
				$hash = md5($title);
				$seeds = 0;
				$leechs = 0;

				$this->debug("[" . $category . "] " . $title . " " . $download . " " . $size);				
				$plugin->addResult($title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category);
				$count++;
			}
		}
		return $count;
	}
	
	
	private function debug($log)
	{
		if ($this->debugEnabled==true)
			file_put_contents('/tmp/torrentsoda.log', $log . "\r\n\r\n", FILE_APPEND);
	}
}


// ---------------------------------------------------- 
// http://php.net/manual/en/function.mb-detect-encoding.php#113983
// ---------------------------------------------------- 
if ( !function_exists('mb_detect_encoding') ) { 

// ---------------------------------------------------------------- 
function mb_detect_encoding ($string, $enc=null, $ret=null) { 
       
        static $enclist = array( 
            'UTF-8', 'ASCII', 
            'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5', 
            'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10', 
            'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16', 
            'Windows-1251', 'Windows-1252', 'Windows-1254', 
            );
        
        $result = false; 
        
        foreach ($enclist as $item) { 
            $sample = iconv($item, $item, $string); 
            if (md5($sample) == md5($string)) { 
                if ($ret === NULL) { $result = $item; } else { $result = true; } 
                break; 
            }
        }
        
    return $result; 
} 
// ---------------------------------------------------------------- 

} 
// ---------------------------------------------------- 
?>