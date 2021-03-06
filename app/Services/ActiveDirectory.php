<?php namespace App\Services;

/**
 * Created by PhpStorm.
 * User: rafag
 * Date: 5/27/15
 * Time: 9:45 AM
 */

use App\Http\Controllers\Change_OrgController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class ActiveDirectory
{
    private static $conn;

    private function __construct() { }


    public static function get_connection()
    {

        if (!ActiveDirectory::$conn)
        {

//            ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
            ActiveDirectory::$conn = ldap_connect("ldap://" . env('LDAP_HOST'));

            if (!ActiveDirectory::$conn)
            {
                error_log(ldap_error(ActiveDirectory::$conn));
                return null;
            }
            else
            {
                $adUserName = env('LDAP_USER');
                $adPassword = env('LDAP_PASSWORD');
                $adDomain = env('LDAP_DOMAIN');

                ldap_set_option(ActiveDirectory::$conn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option(ActiveDirectory::$conn, LDAP_OPT_REFERRALS, 0);
                ldap_set_option(ActiveDirectory::$conn, LDAP_OPT_SIZELIMIT, 1000); //this is just for speed.

                if (!ldap_bind(ActiveDirectory::$conn, $adUserName . "@" . $adDomain, $adPassword))
                {
//                    echo ldap_error(ActiveDirectory::$conn);
//                    die;
                    return null;
                }
            }
        }

        return new ActiveDirectory();
    }

    public function disableUser($entry)
    {
        $dn = $entry[0]["dn"];
        $ac = $entry[0]["useraccountcontrol"][0];
        $disable = ($ac | 2); // set all bits plus bit 1 (=dec2)
        $userdata = array();
        $userdata["useraccountcontrol"][0] = $disable;

        return ldap_modify(static::$conn, $dn, $userdata); //change state

    }

    public function removeFromGroups($groups, $dn)
    {

        if (!isset($dn))
        {
            return false;
        }

        $group_info['member'] = $dn;

        //remove from department group
        $result = ActiveDirectory::query("distinguishedName={$dn}");
        if (isset($result[0]['department'][0]))
        {
            switch ($result[0]['department'][0])
            {
                case "Customer Care":
                    @ldap_mod_del(static::$conn, 'CN=Customer Care NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
                    break;
                case "Finance":
                    @ldap_mod_del(static::$conn, 'CN=Finance NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
                    break;
                case "Human Resources":
                    @ldap_mod_del(static::$conn, 'CN=Human Resources NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
                    break;
            }
        }

        // get group dn
        foreach ($groups as $item)
        {
            $result = ActiveDirectory::query("sAMAccountName={$item}");
            $group_dn = $result[0]['dn'];
            $errorFound = @ldap_mod_del(static::$conn, $group_dn, $group_info);
        }

        $result = false;
    }

    public static function query($search)
    {
        // fetch the info from AD
        $attributes = array('dn', 'title', 'givenname', 'sn', 'manager', 'company', 'department', "memberOf",
            'samaccountname', 'mail');

        $result = ldap_search(static::$conn, "OU=North America,DC=ILLY-DOMAIN,DC=COM", $search, $attributes);

        return ldap_get_entries(static::$conn, $result);
    }

    public function removeUserInfo($entry)
    {

        $dn = $entry[0]["dn"];

        if (isset($entry[0]["description"]))
        {
            $userdata['Description'] = array();
        }
        if (isset($entry[0]["title"]))
        {
            $userdata['title'] = array();
        }
        if (isset($entry[0]["manager"]))
        {
            $userdata['manager'] = array();
        }

        if (isset($userdata))
        {
            return @ldap_mod_del(static::$conn, $dn, $userdata);
        }


    }

    public function getEmail($email)
    {

        if (env('APP_STATUS') == 'offline')
        {
            $offline["givenname"] = 'Rafael';
            $offline["sn"] = 'Gil';
            $offline["mail"] = $email;
            $offline["department"] = 'Information Technology';

            $offline["title"] = 'Infrastructure Engineer';


            $offline["company"] = 'illy caffè North America, Inc.';


            $offline["telephonenumber"] = '+1 914 253 4562';

            $offline["mobile"] = '+1 914 420 3700';


            $offline["sAMAccountName"] = 'gilra';


            $offline["manager"] = 'Roy Forster';
            $offline["managerEmail"] = 'roy.forster@illy.com';

            return $offline;

        }
        else
        {

            $attributes = array('dn', 'title', 'givenname', 'sn', 'manager', 'company', 'department', "memberOf",
                'samaccountname', 'mail', 'mobile', 'telephoneNumber');

            $result = ldap_search(ActiveDirectory::$conn, "OU=North America,DC=ILLY-DOMAIN,DC=COM", "mail={$email}", $attributes);

            return ldap_get_entries(ActiveDirectory::$conn, $result);
        }

    }

    public function createUserAD(Request $req)
    {

        $name = trim($req->request->get('name'));
        $lastName = trim($req->request->get('lastName'));

        $ldaprecord['cn'] = ucfirst(strtolower($name)) . " " . ucfirst(strtolower($lastName));
        $ldaprecord['givenName'] = ucfirst(strtolower($name));
        $ldaprecord['sn'] = ucfirst(strtolower($lastName));
        $ldaprecord['title'] = ucwords(strtolower($req->request->get('title')));
        $ldaprecord['Description'] = ucwords(strtolower($req->request->get('title')));
        $ldaprecord['sAMAccountName'] = strtolower(substr($lastName, 0, 5) . substr($name, 0, 2));
        $ldaprecord['UserPrincipalName'] = $ldaprecord['sAMAccountName'] . "@ILLY-DOMAIN.COM";
        $ldaprecord['displayName'] = ucfirst(strtolower($lastName)) . " " . ucfirst(strtolower($name));

        $ldaprecord['department'] = $req->request->get('department');
        $ldaprecord['company'] = $req->request->get('company');


        switch ($req->request->get('company'))
        {
            case "illy caffè North America, Inc":
                switch ($req->request->get('location'))
                {
                    case "New York City":
                        $ldaprecord['streetAddress'] = \Config::get('app.illy caffè North America, Inc - nyc')['streetAddress'];
                        $ldaprecord['st'] = \Config::get('app.illy caffè North America, Inc - nyc')['st'];
                        $ldaprecord['postalCode'] = \Config::get('app.illy caffè North America, Inc - nyc')['postalCode'];
                        $ldaprecord['l'] = \Config::get('app.illy caffè North America, Inc - nyc')['l'];
                        $ldaprecord['c'] = \Config::get('app.illy caffè North America, Inc - nyc')['c'];
                        break;
                    case "Canada":
                        $ldaprecord['streetAddress'] = \Config::get('app.illy Espresso Canada')['streetAddress'];
                        $ldaprecord['st'] = \Config::get('app.illy Espresso Canada')['st'];
                        $ldaprecord['postalCode'] = \Config::get('app.illy Espresso Canada')['postalCode'];
                        $ldaprecord['l'] = \Config::get('app.illy Espresso Canada')['l'];
                        $ldaprecord['c'] = \Config::get('app.illy Espresso Canada')['c'];
                        break;
                    default:
                        $ldaprecord['st'] = "NY";
                        $ldaprecord['postalCode'] = "10573";
                        $ldaprecord['l'] = "Rye Brook";
                        $ldaprecord['c'] = "US";
                        $ldaprecord['streetAddress'] = "800 Westchester Avenue, Suite S440";
                        break;
                }
                break;
            case "Espressamente illy":
                $ldaprecord['st'] = \Config::get('app.Espressamente illy')['st'];
                $ldaprecord['postalCode'] = \Config::get('app.Espressamente illy')['postalCode'];
                $ldaprecord['l'] = \Config::get('app.Espressamente illy')['l'];
                $ldaprecord['c'] = \Config::get('app.Espressamente illy')['c'];
                $ldaprecord['streetAddress'] = \Config::get('app.Espressamente illy')['streetAddress'];
                break;
            case "illy caffè San Francisco LLC":
                $ldaprecord['st'] = \Config::get('app.illy caffè San Francisco LLC')['st'];
                $ldaprecord['postalCode'] = \Config::get('app.illy caffè San Francisco LLC')['postalCode'];
                $ldaprecord['l'] = \Config::get('app.illy caffè San Francisco LLC')['l'];
                $ldaprecord['c'] = \Config::get('app.illy caffè San Francisco LLC')['c'];
                $ldaprecord['streetAddress'] = \Config::get('app.illy caffè San Francisco LLC')['streetAddress'];
                break;
        }

        switch ($req->request->get('location'))
        {
            case "Canada":
                $ldaprecord['streetAddress'] = \Config::get('app.illy Espresso Canada')['streetAddress'];
                $ldaprecord['st'] = \Config::get('app.illy Espresso Canada')['st'];
                $ldaprecord['postalCode'] = \Config::get('app.illy Espresso Canada')['postalCode'];
                $ldaprecord['l'] = \Config::get('app.illy Espresso Canada')['l'];
                $ldaprecord['c'] = \Config::get('app.illy Espresso Canada')['c'];
                break;
        }

        $ldaprecord['displayName'] = ucfirst(strtolower($lastName)) . " " . ucfirst(strtolower($name));

        if ($req->request->get('location_Other') != '')
        {
            $ldaprecord['physicalDeliveryOfficeName'] = $req->request->get('location_Other');
        }
        else
        {
            $ldaprecord['physicalDeliveryOfficeName'] = $req->request->get('location');
        }
        $ldaprecord['telephoneNumber'] = "+1 914 253 4";
        $ldaprecord['UserAccountControl'] = "544";
        $ldaprecord['objectclass'][0] = 'top';
        $ldaprecord['objectclass'][1] = 'person';
        $ldaprecord['objectclass'][2] = 'organizationalPerson';
        $ldaprecord['objectclass'][3] = 'user';
        $ldaprecord['mail'] = strtolower($name . "." . $lastName) . "@illy.com";

        //search manager DN by email
        if ($req->request->get('managerEmail') != '')
        {
            $consult = ldap_search(static::$conn, "OU=North America,DC=ILLY-DOMAIN,DC=COM", "mail={$req->request->get('managerEmail')}", ['distinguishedName']);
            $managerInfo = ldap_get_entries(static::$conn, $consult);
            if (isset($managerInfo[0]['distinguishedname'][0]))
            {
                $ldaprecord["manager"] = $managerInfo[0]['distinguishedname'][0];
            }
        }

        //create the user in the system
        $dn_user = ("CN=" . $ldaprecord['cn'] . ",OU=Users,OU=" . $req->request->get('location') . ",OU=North America,DC=ILLY-DOMAIN,DC=COM");
        @ldap_add(static::$conn, $dn_user, $ldaprecord);

        //define and join user to groups
        $illyGroups['illyusaNorth America'] = 'CN=illyusaTeam Distribution Group,OU=Distribution Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM';
        $illyGroups['illyryebrook'] = 'CN=illyusa Rye Brook Distribution Group,OU=Distribution Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM';
        $illyGroups['illyusa NYC Team'] = 'CN=illy NYC Team Distribution Group,OU=Distribution Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM';
        $illyGroups['illyCanada'] = 'CN=illy Canada Distribution Group,OU=Distribution Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM';
        $illyGroups['illyManagers'] = 'CN=illyusa Managers Distribution Group,OU=Distribution Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM';
        $illyGroups['illySales'] = 'CN=illyusa Sales Team Distribution Group,OU=Distribution Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM';

        $group_info['member'] = $dn_user;

        $groupsToAdd = $req->request->get('iTDeptEmail');

        //add the user to the default groups
        @ldap_mod_add(static::$conn, 'CN=VPN_usa,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
        @ldap_mod_add(static::$conn, 'CN=WIFI_usa,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);

        //check if application is checked add to JDE group
        if ($req->request->get('application') != '')
        {
            @ldap_mod_add(static::$conn, 'CN=JDE USA Remote,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
        }

        if (isset($groupsToAdd))
        {
            foreach ($groupsToAdd as $group)
            {
                @ldap_mod_add(static::$conn, $illyGroups[$group], $group_info);
            }
        }

        // add the department group
        switch ($req->request->get('department'))
        {
            case "Customer Care":
                @ldap_mod_add(static::$conn, 'CN=Customer Care NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
                break;
            case "Finance":
                @ldap_mod_add(static::$conn, 'CN=Finance NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
                break;
            case "Human Resources":
                @ldap_mod_add(static::$conn, 'CN=Human Resources NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
                break;
            case "Marketing":
                @ldap_mod_add(static::$conn, 'CN=Marketing NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
                break;
            case "Human Resources":
                @ldap_mod_add(static::$conn, 'CN=Human Resources NA,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM', $group_info);
                break;
        }

    }

    /**
     * @param $manager
     *
     * Get the email, sn and givenname of the manager expects a dn as a parameter
     *
     * @return array
     */
    public function getManager($manager)
    {
        $consult = ldap_search(static::$conn, $manager, "(objectclass=*)", ['mail', 'sn', 'givenname']);

        return ldap_get_entries(static::$conn, $consult);

    }

    public function lookup_chng_org(Request $req)
    {
        $consult = $this->lookupUser($req->request->get('term') . '*');
        $result = [];
        for ($i = 0; $i < $consult["count"]; $i++)
        {
            if (isset($consult[$i]["givenname"][0]) && isset($consult[$i]["sn"][0]) && isset($consult[$i]["samaccountname"][0]))
            {
                $result[] = array("label" => $consult[$i]["givenname"][0] . ' ' . $consult[$i]["sn"][0],
                    "value" => $consult[$i]["samaccountname"][0]);
            }
        }

        return new Response($result, 200, ['content-type' => 'application/json']);
    }

    private static function lookupUser($param)
    {

        // fetch the info from AD
        $attributes = array('givenname', 'sn', 'mail', 'samaccountname', 'mail');

        $mail = str_replace(' ', '.', $param);

        $result = ldap_search(static::$conn, "OU=North America,DC=ILLY-DOMAIN,DC=COM", "(&(!(userAccountControl:1.2.840.113556.1.4.803:=2)) ( |(|(givenname={$param})(sn={$param})) (mail={$mail}))  )", $attributes);

        return ldap_get_entries(static::$conn, $result);

    }

    public function autocomplete(Request $req)
    {

        $consult = $this->lookupUser('*' . $req->request->get('term') . '*');

        $result = [];
        for ($i = 0; $i < $consult["count"]; $i++)
        {
            if (isset($consult[$i]["givenname"][0]) && isset($consult[$i]["sn"][0]) && isset($consult[$i]["mail"][0]))
            {
                if (preg_match("/@illy.com/", $consult[$i]["mail"][0]))
                {
                    $result[] = array("label" => $consult[$i]["givenname"][0] . ' ' . $consult[$i]["sn"][0],
                        "value" => $consult[$i]["mail"][0]);
                }
            }
        }

        return json_encode($result);
    }

    public function validateLogin($userName, $password)
    {
        $attributes = array('givenname', 'sn', 'sAMAccountName');
        $result = ldap_search(static::$conn, "OU=North America,DC=ILLY-DOMAIN,DC=COM", "(&(sAMAccountName={$userName})(memberOf=CN=HR-Tool,OU=Security Groups,OU=Rye Brook,OU=North America,DC=ILLY-DOMAIN,DC=COM))", $attributes);
        $entry = ldap_get_entries(static::$conn, $result);

        if (isset($entry[0]["count"]))
        {
            //verify the password
            if (@$bind = ldap_bind(static::$conn, $userName . "@" . env('LDAP_DOMAIN'), $password))
            {
                return $entry;
            } // else password incorrect

            ldap_close(static::$conn);
        }
        else
        {
            return false;
        } // username incorrect or not allowed to login

    }

    public function getsamaccountname($username)
    {
        $attributes = array('givenname', 'sn','dn', 'useraccountcontrol', 'Description', 'title', 'mail', 'manager', 'company');
        $myDN = "OU=North America,DC=ILLY-DOMAIN,DC=COM";

        $txtSearch = "samaccountname={$username}";

        $result = ldap_search(static::$conn, $myDN, $txtSearch, $attributes);

        return ldap_get_entries(static::$conn, $result);
    }

    /**
     * @param $dn
     * @param $changes
     * @param $fromAD
     */
    public function change_org_Save($dn, $changes, $fromAD)
    {

        $notifyChanges = [];
        $notifyCurrentInfo = [];


        foreach ($changes as $key => $value)
        {
            $userdata[$key] = $value;
        }

        if (isset($changes['givenname']))
        {
            $name = $changes['givenname'];
            $notifyChanges['givenname'] = $name;
            $notifyCurrentInfo['givenname'] = $fromAD[0]['givenname'][0];
        }
        else
        {
            $name = $fromAD[0]['givenname'][0];
        }

        if (isset($changes['sn']))
        {
            $lastName = $changes['sn'];
            $notifyChanges['sn'] = $lastName;
            $notifyCurrentInfo['sn'] = $fromAD[0]['sn'][0];

        }
        else
        {
            $lastName = $fromAD[0]['sn'][0];
        }

        if(isset($notifyChanges['givenname']) || isset($notifyChanges['sn']))
            $userdata['displayName'] = ucfirst(strtolower($lastName)) . " " . ucfirst(strtolower($name));

        if (isset($changes['title']))
        {
            $userdata['description'] = $changes['title'];
            $notifyChanges['title'] = $changes['title'];
            $notifyCurrentInfo['title'] = $fromAD[0]['title'][0];

        }

        if (isset($changes['manager']))
        {
            $userdata['manager'] = $fromAD['newManager'];
        }


        if (isset($changes['company']))
        {
            $userdata['st'] = \Config::get('app.' . $changes['company'] . '.st');
            $userdata['postalCode'] = \Config::get('app.' . $changes['company'] . '.postalCode');
            $userdata['l'] = \Config::get('app.' . $changes['company'] . '.l');
            $userdata['c'] = \Config::get('app.' . $changes['company'] . '.c');
            $userdata['streetAddress'] = \Config::get('app.' . $changes['company'] . '.streetAddress');
            $notifyChanges['company'] = $changes['company'];
            $notifyCurrentInfo['company'] = $fromAD[0]['company'][0];
        }
        else
        {
            if (!in_array($fromAD[0]['company'][0], \Config::get('app.companies')))
            {
                $userdata['company'] = 'illy caffè North America, Inc';
                $userdata['st'] = "NY";
                $userdata['postalCode'] = "10573";
                $userdata['l'] = "Rye Brook";
                $userdata['c'] = "US";
                $userdata['streetAddress'] = "800 Westchester Avenue, Suite S440";
            }
        }

        @ldap_mod_replace(static::$conn, $dn, $userdata);

        // if the change is info that appears on the signature send an email to the user to update it
        if (count($notifyChanges) >= 1)
        {
           Change_OrgController::update_signature($name . ' ' . $lastName, $fromAD[0]['mail'][0] , $notifyChanges, $notifyCurrentInfo);
        }


    }

    private function __clone() { }

    private function __wakeup() { }

}