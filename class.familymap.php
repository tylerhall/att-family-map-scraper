<?PHP
    // Sample Usage
    // $fm = new FamilyMap();
    // $coords = $fm->locate('1234567890', 'mypassword');

    class FamilyMap
    {
        const URL_BASE  = 'https://familymap.att.com/finder-wap-att/';
        const URL_LOGIN = 'https://familymap.att.com/finder-wap-att/login.htm';
        const URL_MAIN  = 'https://familymap.att.com/finder-wap-att/main.htm';

        // const FE_KEY    = '';
        // const FE_SECRET = '';
        //
        // const FE_ACCESS_TOKEN  = '';
        // const FE_ACCESS_SECRET = '';

        private $lastURL;

        public function __construct()
        {

        }

        public function locate($phone, $password)
        {
            // Login
            $phone = urlencode($phone);
            $password = urlencode($password);
            $html = $this->curl(FamilyMap::URL_LOGIN, FamilyMap::URL_LOGIN, "mdn=$phone&password=$password");

            // Grab locate link
            $link = $this->match('/(main\.htm\?ri=[0-9]+)/ms', $html, 1);

            // Load location page
            $max_attempts = 10;
            do
            {
                if(isset($img)) sleep(2);
                $html = $this->curl(FamilyMap::URL_BASE . $link, FamilyMap::URL_MAIN);
                $img = $this->match('/zoomed\.png\?a=(.*?)(\'|")/ms', $html, 1);
            }
            while($img === false && --$max_attempts > 0);

            if($img === false)
                return false;

            // https://familymap.att.com/finder-wap-att/map/zoomed.png?a=-86.7844444,36.1658333,1544,0.0,0.0,0,,-86.7844444,36.1658333,12.352,0,0
            // 0 = user lng
            // 1 = user lat
            // 7 = map lng
            // 8 = map lat
            $info = explode(',', $img);

            $this->updateFireEagle($info[1], $info[0]);
            return array('lat' => $info[1], 'lng' => $info[0]);
        }

        private function updateFireEagle($lat, $lng)
        {
            // You'll need to download and include Fire Eagle's PHP API Kit to update Fire Eagle.
            // Available here: http://fireeagle.yahoo.net/developer/code/php

            // $fe = new FireEagle(FamilyMap::FE_KEY, FamilyMap::FE_SECRET, FamilyMap::FE_ACCESS_TOKEN, FamilyMap::FE_ACCESS_SECRET);
            // $fe->update(array('q' => "$lat, $lng"));
        }

        private function curl($url, $referer = null, $post = null, $return_header = false)
        {
            static $tmpfile;

            if(!isset($tmpfile) || ($tmpfile == '')) $tmpfile = tempnam('/tmp', 'FOO');

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpfile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpfile);
            // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; U; CPU iPhone OS 2_2_1 like Mac OS X; en-us) AppleWebKit/525.18.1 (KHTML, like Gecko) Version/3.1.1 Mobile/5H11 Safari/525.20");
            if($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);

            if(!is_null($post))
            {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }

            if($return_header)
            {
                curl_setopt($ch, CURLOPT_HEADER, 1);
                $html        = curl_exec($ch);
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $this->lastURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                return substr($html, 0, $header_size);
            }
            else
            {
                $html = curl_exec($ch);
                $this->lastURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                return $html;
            }
        }

        private function match($regex, $str, $i = 0)
        {
            return preg_match($regex, $str, $match) == 1 ? $match[$i] : false;
        }
    }
