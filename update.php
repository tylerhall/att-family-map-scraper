<?PHP
    require 'lib/config.php';
    require 'lib/fireeagle.php';
    require 'lib/familymap.php';

    $fm = new FamilyMap();
    $coords = $fm->locate($fm_phone, $fm_password);
    if(is_array($coords))
    {
        $fe = new FireEagle($fe_key, $fe_secret, $fe_access_token, $fe_access_secret);
        $fe->update(array('q' => $coords['lat'] . ', ' . $coords['lng']));
        echo "Yay!";
    }
    else
    {
        echo "Fail.";
    }
