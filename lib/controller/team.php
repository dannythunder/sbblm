<?php

namespace Controller;

class Team {

    public function getOne($f3, $params) {
        $id = intval($params['id']);
        $team = new \Model\Team();
        $team->load(array('id=?', $id));

        if($team->dry()) {
            $f3->error(404);
        } else {
            $f3->set('team', $team);
            $f3->set('page.title', $team->name);
            $f3->set('page.template', "teamView");

            echo \Template::instance()->render('layout.html');
        }
    }

    public function edit($f3, $params) {
        $team = new \Model\Team();
        if(!empty($params['id'])) {
            $id = intval($params['id']);
            $team->load(array('id=?', $id));
            $new = false;
        } else {
            $new = true;
        }

        $list = new \Model\PositionList();
        $lists = $list->find();
        $f3->set("lists", $lists);

        if($new || !$team->dry()) {
            $f3->set('team', $team);
            if($new) {
                $f3->set('page.title', "New team");
            } else
                $f3->set('page.title', $team->name." - Edit");
            $f3->set('page.template', "teamEdit");

            echo \Template::instance()->render('layout.html');
        } else {
            $f3->error(404);
        }
    }

    public function update($f3, $params) {
        $team = new \Model\Team();
        if(!empty($params['id'])) {
            $id = intval($params['id']);
            $team->load(array('id=?', $id));

            $existing = $team->players;
        }
        $p = $f3->get("POST");
        //Getting the data
        $team->name = $p['teamname'];
        $team->coach = $f3->get("coach");
        $team->money = $p['money'];
        $team->list = $p['race'];
        $team->value = $p['value'];
        $team->FF = $p['ff'];
        $team->rerolls = $p['rerolls'];
        $team->assistants = $p['assistants'];
        $team->cheerleaders = $p['cheerleaders'];
        $team->apothecary = $p['apothecary'];
        // Add the currently logged in coach as team coach
        $team->save();

        //Now, get each player.
        $saved = array();
        foreach($p['player'] as $post) {
            $player = new \Model\Player();
            if(!empty($post['id'])) {
                $pl_id = intval($post['id']);
                $player->load(array('id=?', $pl_id));
            }

            $player->name = trim($post['name']);
            $player->position = $post['position'];
            $player->number = $post['number'];
            $player->MA = $post['ma'];
            $player->AG = $post['ag'];
            $player->ST = $post['st'];
            $player->AV = $post['av'];
            $player->basicSkills = $post['basicskills'];
            $player->learnedSkills = $post['learnedskills'];
            $player->CP = $post['cp'];
            $player->TD = $post['td'];
            $player->Int = $post['int'];
            $player->Cas = $post['cas'];
            $player->MVP = $post['mvp'];
            $player->SPP = $post['spp'];
            $player->level = $post['level'];
            $player->value = $post['playervalue'];
            $player->team = $team;
            $player->save();
            $saved[$player->id] = true;
        }
        //Now, let's delete the ones that were not on the POSTed array
        foreach($existing?:[] as $old) {
            if(empty($saved[$old->id])) {
                $old->erase();
            }
        }

        if(empty($params['id'])) {
            $file = $f3->get('FILES'); 
            $img = new \Image($file['logo']['tmp_name'], false, '');
            $img->resize(180, 180, false, false);
            $slug = trim($team->name);
            $slug = transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();", $slug);
            $slug = preg_replace('/[-\s]+/', '-', $slug);
            $name = "img/teamlogos/$slug.png";
            $f3->write( $name, $img->dump('png') );


            /*
            $f3->set("team", $team);
            $f3->set('UPLOADS', 'img/teamlogos/');
            $web = new \Web();
            $file = $web->receive(function($file) {
                if($file['size'] > (2 * 1024 * 1024)) // if bigger than 2 MB
                    return false; // this file is not valid, return false will skip moving it
                return true;
            },true, function() { return \Base::instance()->get("team")->name.".png"; });

            $name = array_keys($file)[0];
             */
            $team->logo = $name;
        }

        $team->save();

        $f3->reroute("@team_view(@id=".$team->id.")");
    }

    public function uploadLogo($f3, $params) {
        $id = intval($params['id']);
        $team = new \Model\Team();
        $team->load(array('id=?', $id));

        if($team->dry()) {
            $f3->error(404);
        } else {
            $file = $f3->get('FILES'); 
            $img = new \Image($file['logo']['tmp_name'], false, '');
            $img->resize(180, 180, false, false);
            $slug = trim($team->name);
            $slug = transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();", $slug);
            $slug = preg_replace('/[-\s]+/', '-', $slug);
            $name = "img/teamlogos/$slug.png";
            $f3->write( $name, $img->dump('png') );
            $team->logo = $name;

            /*

            $f3->set("team", $team);

            $f3->set('UPLOADS', 'img/teamlogos/');
            $web = new \Web();
            $file = $web->receive(function($file) {
                if($file['size'] > (2 * 1024 * 1024)) // if bigger than 2 MB
                    return false; // this file is not valid, return false will skip moving it
                return true;
            },true, function() { return \Base::instance()->get("team")->logo.".png"; });

            $name = array_keys($file)[0];

            $team->logo = $name;
             */
            $team->save();

            echo json_encode(array('code' => 0, 'url' => $f3->get("BASE")."/".$name));
        }
    }

}
