<?php 
// composer autoloader for required packages and dependencies
require_once('../vendor/autoload.php');
/** @var \Base $f3 */
$f3 = \Base::instance();
// F3 autoloader for application business code
$f3->config('../config.ini');
$f3->set('DB', new \DB\SQL('sqlite:'.$f3->get('DB_NAME')));
//new \DB\SQL\Session($f3->get("DB"));

// Error handling
$f3->set('ONERROR',
    function($f3) {
        switch ($f3->get('ERROR.code')) {
        case 404: echo \Template::instance()->render('404.html'); break;
        case 500: echo "<h2>Error ".$f3->get('ERROR.code')."</h2><p>".$f3->get("ERROR.status")."</p><p>".$f3->get("ERROR.text")."</p><div><pre>".$f3->get("ERROR.trace")."</pre></div>"; break;
        default: echo "<h2>Big mistake nº".$f3->get('ERROR.code')."</h2><p>".$f3->get("ERROR.status")."</p>"; break;
        }
    }
);

// Load League config (if present)
$f3->set("cfg.logo", \Model\Config::read("logo"));
$f3->set("cfg.name", \Model\Config::read("name"));
$f3->set("cfg.welcome", \Model\Config::read("welcome"));
$f3->set("cfg.initialgold", \Model\Config::read("initialgold"));
$f3->set("cfg.ff", \Model\Config::read("ff"));
$f3->set("cfg.ffPrice", \Model\Config::read("ffPrice"));


$f3->set("LOGGEDIN", (\Controller\Auth::role($f3) != 'guest'));
$f3->set("MANAGER", (\Controller\Auth::role($f3) == 'manager'));

// Home
$f3->route("GET @home: /", function($f3) {
    $f3->set("news", (new \Controller\News)->getLast($f3));
    $f3->set("page.template", "home");
    echo Template::instance()->render('layout.html');
});

// Asset management
$f3->route('GET /assets/@type',
	function($f3, $args) {
        $path = $f3->get('UI').$args['type'].'/';
        if($args['type'] == 'less') {
            $parser = new Less_Parser(array('compress'=>true));
            $files = $_GET['files'];
            foreach(explode(",", $files) as $file) 
                $parser->parseFile($path.$file);

            header('Content-type: text/css');
            echo $parser->getCss();
        } else {
            $files = preg_replace('/(\.+\/)/','',$_GET['files']); // close potential hacking attempts  
            echo \Preview::instance()->resolve(\Web::instance()->minify($files, null, true, $path));
        }
	},
	3600*24
);

// Login and auth
$f3->route('GET @login: /login', '\Controller\Auth->login');
$f3->route('POST @login_check: /login_check', '\Controller\Auth->check');
$f3->route('GET @logout: /logout', '\Controller\Auth->logout');

// Route access
$access = Access::instance();
$access->policy('allow');
$access->deny('/config');
$access->allow('/config', 'manager');
$access->deny('/coach/*');
$access->allow('/coach/*', 'manager');
$access->allow('/coach/@/view');
$access->allow('/coach/profile', 'coach');
$access->allow('/coach/profile/edit', 'coach');
$access->deny('/team/*');
$access->allow('/team/*', 'coach,manager');
$access->allow('/team/@/view', 'guest');
$access->deny('/news/*');
$access->allow('/news/*', 'manager');
$access->deny('/season/@/edit');
$access->deny('/season/new');
$access->allow('/season/@/edit', 'manager');
$access->allow('/season/new', 'manager');

$access->authorize(\Controller\Auth::role($f3));

// Normal routes
$f3->route('GET @coach_list: /coaches', '\Controller\Coach->getList');
$f3->route('GET @coach_view: /coach/@id/view', '\Controller\Coach->getOne');
$f3->route('GET @coach_new: /coach/new', '\Controller\Coach->edit');
$f3->route('GET @coach_edit: /coach/@id/edit', '\Controller\Coach->edit');
$f3->route('POST @coach_update: /coach/@id/update', '\Controller\Coach->update');
$f3->route('POST @coach_create: /coach/create', '\Controller\Coach->update');
$f3->route('POST @coach_avatar: /coach/@id/avatar', '\Controller\Coach->uploadAvatar');
$f3->route('GET @coach_delete: /coach/@id/delete', '\Controller\Coach->delete');

$f3->route('GET @coach_profile: /coach/profile', '\Controller\Coach->profile');
$f3->route('GET @coach_profile_edit: /coach/profile/edit', '\Controller\Coach->profileEdit');

$f3->route('GET @team_view: /team/@id/view', '\Controller\Team->getOne');
$f3->route('GET @team_new: /team/new', '\Controller\Team->edit');
$f3->route('GET @team_edit: /team/@id/edit', '\Controller\Team->edit');
$f3->route('POST @team_update: /team/@id/update', '\Controller\Team->update');
$f3->route('POST @team_create: /team/create', '\Controller\Team->update');
$f3->route('POST @team_logo: /team/@id/logo', '\Controller\Team->uploadLogo');

$f3->route('GET @race_getpositions: /race/@id/getlist', '\Controller\PositionList->getPositions');
$f3->route('GET @skills_list: /skills/getlist', '\Controller\Skill->getList');

$f3->route('GET @config: /config', '\Controller\Config->create');
$f3->route('POST @config: /config', '\Controller\Config->save');

$f3->route('GET @news_edit: /news/@id/edit', '\Controller\News->edit');
$f3->route('GET @news_new: /news/new', '\Controller\News->edit');
$f3->route('POST @news_update: /news/@id/update', '\Controller\News->update');
$f3->route('POST @news_create: /news/create', '\Controller\News->update');

$f3->route('GET @season_view: /season/@id/view', '\Controller\Season->getOne');
$f3->route('GET @season_list: /seasons', '\Controller\Season->getList');
$f3->route('GET @season_edit: /season/@id/edit', '\Controller\Season->edit');
$f3->route('GET @season_new: /season/new', '\Controller\Season->edit');
$f3->route('POST @season_update: /season/@id/update', '\Controller\Season->update');
$f3->route('POST @season_create: /season/create', '\Controller\Season->update');

// Install
$f3->route('GET /install', function() {
    echo "<h3>Creando bases de datos...</h3>";
    $models = array('Coach', 'Game', 'Config', 'Player', 'PositionList', 'Position', 'Season', 'SkillList', 'Skill', 'Team', 'News');
    foreach($models as $model) {
        $class = "\Model\\$model";
        if( $class::setup() )
            echo "<p>Tabla <code>$model</code> creada</p>";
        else
            echo "<p>No se ha podido crear la tabla <code>$model</code>.</p>";
    }
    echo "<h3>Cargando lista de habilidades....</h3>";
    //\Model\Skill::import("../rules/skills.csv");
    echo "<h3>Cargando listas de equipo....</h3>";
    //\Model\PositionList::import("../rules/teams.csv");
    echo "<h3>Cargando lista de posiciones....</h3>";
    //\Model\Position::import("../rules/positions.csv");
});

\Assets::instance();
$f3->run();
