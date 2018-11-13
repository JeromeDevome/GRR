<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AreaController extends AbstractController
{
    public function __construct()
    {
    }

    /**
     * @Route("/area", name="area")
     */
    public function index()
    {
        global $vocab, $search_str, $grrSettings, $desactive_VerifNomPrenomUser, $grr_script_name;
        global $use_admin, $id_site, $use_select2, $lienRetour, $lienCompte, $nomAffichage;

        $page = 'admin_accueil';
        if (isset($_GET['p'])) {
            $page = $_GET['p'];
        }
        // GRR
        include __DIR__."/../../include/admin.inc.php";

        $back = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $back = htmlspecialchars($_SERVER['HTTP_REFERER']);
        }
        if ((authGetUserLevel(getUserName(), -1, 'area') < 4) && (authGetUserLevel(getUserName(), -1, 'user') != 1)) {
            showAccessDenied($back);
            exit();
        }
        print_header_admin("", "", "", $type = "with_session");

        get_vocab_admin('grr_version');
        get_vocab_admin('retour_planning');
        get_vocab_admin("manage_my_account");
        get_vocab_admin("display_add_user");
        get_vocab_admin('admin_view_connexions');

        $trad['dNomAffichage'] = "Admin";
        $trad['dLienRetour'] = "";
        $trad['dLienCompte'] = "";
        $trad['retour_planning'] = "";
        $trad['manage_my_account'] = "";
        $trad['TitrePage'] = "";
        $trad['SousTitrePage'] = "";
        $trad['grr_version'] = "";
        $trad['display_add_user'] = "";
        $trad['admin_view_connexions'] = "";
        $trad['dNomUtilisateur'] = getUserName();
        $AllSettings = \Settings::getAll();

        // Menu GRR
        $menuAdminT = array();
        $menuAdminTN2 = array();
        include __DIR__."/../../admin/admin_col_gauche.php";

        return $this->render(
            '@foo_bar/admin_accueil.twig',
            array(
                'liensMenu' => $menuAdminT,
                'liensMenuN2' => $menuAdminTN2,
                'trad' => $trad,
                'settings' => $AllSettings,
            )
        );
    }
}
