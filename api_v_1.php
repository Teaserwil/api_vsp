<?php
header("Content-type:application/json");
/*авторизация json-клиента*/
if($_SERVER['HTTP_AUTHORIZATIONAPI'] != 'your_key'){
    $output['message'] = 'Ошибка авторизации json-клиента';
    return json_encode($output);
    die();
}


function getContent($id){
      global $modx;
        $resourcesContent = $modx->runSnippet('pdoResources',array(
        	'includeTVs'=>'image,multiTV_nedelka,sostav,app_mozhno,app_nelzya,recipeCategory',
        	'processTVs'=>1,
        	'parents'=>'0',
        	'includeContent'=>1,
        	'resources'=>$id,
        	'limit'=>0,
        	'showUnpublished'=>0,
        	'return'=>json
           )
        );
       preg_match("/<form(.*)<\/form>/sU",$resourcesContent,$badcontent);
       if($badcontent){
            $clear_badcontent = json_encode($badcontent[0]);
            $clear_badcontent = trim($clear_badcontent, '"');
            //$clear_badcontent=addslashes($badcontent[0]);
            $resourcesContent  = str_replace($badcontent[0], $clear_badcontent, $resourcesContent);
       }
       
       $content = $parentsContent.$resourcesContent;
        if($parentsContent and $resourcesContent){
            $content = str_replace('}][{', '},{', $content);
            
        }
        
        
        preg_match_all('|([\w\W]*content":")(.*)("\,"richtext[\w\W]*)|is', $content, $out, PREG_SET_ORDER);
        $out[0][2] = str_replace(array("\n", '\n', "\r", '\r', "\t", '\t'), "", $out[0][2]);
        $out[0][2] = str_replace('\"', '"', $out[0][2]);
        $out[0][2] = str_replace("'", '"', $out[0][2]);
        $out[0][2] = str_replace('"', '\"', $out[0][2]);
        $out[0][2] = str_replace('width', 'width=\"100%\" width_old', $out[0][2]);
        $out[0][2] = str_replace('height', 'height=\"auto\" height_old', $out[0][2]);
        $out[0][2] = str_replace('<h2', '<h2  style=\"background:linear-gradient(90deg, #7348FF 0%, #F84784 100%);-webkit-background-clip: text;-webkit-text-fill-color: transparent;\"', $out[0][2]);
        // echo $out[0][2];
        // $content = $out[0][1].str_replace('"', "'", $out[0][2]).$out[0][3];
        $content = $out[0][1].$out[0][2].$out[0][3];

        
        
        //доработки через массив, формируем оглавление, 
        //получаем автором по переменным шаблона, добавляем картинку анонса
        $content = json_decode($content);
        foreach($content as $obj_art){
            $page = $modx->getObject('modResource', $obj_art->id);
            $img = $page->getTVValue('image');
            $autors_ar = explode(',',$page->getTVValue('article_author'));
            $soautors_ar = explode(',',$page->getTVValue('article_soauthor'));
            if(count($autors_ar) > 1 ){
                $title_autor = '<h3>Авторы статьи</h3>';
            }
            else{
                $title_autor = '<h3>Автор статьи</h3>';
            }
            if(count($soautors_ar) > 1){
                $title_soautor = '<h3>Саовторы статьи</h3>';
            }
            else{
                $title_soautor = '<h3>Соавтор статьи</h3>';
            }
            if($img != ''){
               $obj_art->content = '<img width="45%" height="auto" style="float:left;width:45%;margin-right:5%;margin-bottom:5%" src="'.$img.'">'.$obj_art->content; 
            }
            preg_match_all("/<h2.*?>(.*?)<\/h2>/i", $obj_art->content, $items);
        	$my_links = '';
        	$hr = '';
        	foreach ($items[1] as $i => $row) {
        	        $first_title = '<h3>Содержание:</h3>';
        	        $hr = '<br><hr style="color:#7348FF;"><br>';
        			$my_links .= '<li style="list-style-type:none;margin-bottom:8px;line-height:18px; "><a style="background:linear-gradient(90deg, #7348FF 0%, #F84784 100%);-webkit-background-clip: text;-webkit-text-fill-color: transparent;font-weight:600;" href="#tag-' . ++$i . '">' . $row . '</a></li>';
        	}
        	foreach ($items[0] as $i => $row) {
        		$obj_art->content = str_replace($row, '<a name="tag-' . ++$i . '"></a>' . $row, $obj_art->content);
        	}
        	$autors_row = '';
        	$style_for_autors = '
        	<style>
        	.tr{
            	display:block;
            	width:95%;
            	margin:auto ;
            	padding-top:5px;
            	background-color: #fff;
            	position:relative;
            	border-radius:8px;
        	}
        	.tr::before{
        	    content: "";
                position: absolute;
                top: -3px;
                bottom: -3px;
                left: -3px;
                right: -3px;
                background: linear-gradient(90deg, #F84784 0%, #7348FF 100%);
                border-radius: 10px;
                z-index: -1;
        	}
        	.author_inside{
        	    position: relative;
        	    min-height: 112px;
        	    margin:auto;
        	    width:calc(100% - 20px);
        	    padding-bottom: 7px;
        	}
        	
            </style>';
            $autors_ids = array();
        	foreach($autors_ar as $autor){
        	    
        	    $obj_autor = $modx->getObject('modResource', $autor);
        	    $autors_ids[] = $obj_autor->get('id');
        	    $my_lis = '';
        	    preg_match_all("/<li.*?>(.*?)<\/li>/i", $obj_autor->getTVValue('doctor_preview_text'), $lis);
        	    foreach ($lis[1] as $i => $row) {
        			$my_lis .= '<p style="font-size:14px;margin-top:0px;margin-bottom:4px;line-height:18px;">'.$row.'</p>';
        	    }
        	    $autors_row .= '<a style="color:unset;text-decoration:none;" href="https://vseopecheni.ru/o-nas/avtoryi/'.$obj_autor->get('alias').'"><div class="tr" style="">
        	                        <div class="author_inside" >
        	                            <div style=" width:100%">
        	                                <img src="'.$obj_autor->getTVValue('image').'" style="float:right;height: 115px;width:auto;border-radius:10px;">
        	                                <h3 style="font-size:20;margin-top:0px;margin-bottom:5px; color:rgba(93, 200, 213, 1)">'.$obj_autor->get('pagetitle').'</h3>
        	                                '.
        	                                    $my_lis
        	                                .'
        	                            </div>
        	                            <div style="width: 40%; position:relative;float:right;">
        	                                
        	                            </div>
        	                        </div>
        	                    </div></a>';
        	}
        	$soautors_row = '';
        	foreach($soautors_ar as $autor){
        	    
        	    $obj_autor = $modx->getObject('modResource', $autor);
        	    if(in_array($obj_autor->get('id'),$autors_ids)){
        	        continue;
        	    }
        	    $my_lis_so = '';
        	    preg_match_all("/<li.*?>(.*?)<\/li>/i", $obj_autor->getTVValue('doctor_preview_text'), $lis);
        	    foreach ($lis[1] as $i => $row) {
        			$my_lis_so .= '<p style="font-size:14px;margin-top:0px;margin-bottom:4px;line-height:18px;">'.$row.'</p>';
        	    }
        	    $soautors_row .= '<a style="color:unset;text-decoration:none;" href="https://vseopecheni.ru/o-nas/avtoryi/'.$obj_autor->get('alias').'"><div class="tr" style="">
        	                        <div class="author_inside" >
        	                            <div style=" width:100%">
        	                                <img src="'.$obj_autor->getTVValue('image').'" style="float:right;height: 115px;width:auto;border-radius:10px;">
        	                                <h3 style="font-size:20;margin-top:0px;margin-bottom:5px; color:rgba(93, 200, 213, 1)">'.$obj_autor->get('pagetitle').'</h3>
        	                                '.
        	                                    $my_lis_so 
        	                                .'
        	                            </div>
        	                            <div style="width: 40%; position:relative;float:right;">
        	                                
        	                            </div>
        	                        </div>
        	                    </div></a>';
        	}
        	if($autors_row != ''){
        	    $autors_row = $style_for_autors.$title_autor.$autors_row;
        	}
        	if($soautors_row != ''){
        	    $autors_row = $autors_row.$title_soautor.$soautors_row;
        	}
        	$obj_art->content = $first_title . $my_links . $hr . $obj_art->content.$autors_row;
            
        }
        
        $content = json_encode($content);
        return $content;
}

/*получаем данные главной страницы*/
if($res_id == 15419){
    $ar_cat = array();
    $res = $modx->getObject('modResource',15419);
    $first_str = $res->getTVValue('main_in_app_api_v1');
    $bad_array = json_decode($first_str);
    $final_array = array();
    foreach($bad_array as $b_a){
        $ar_pr = array();
        if($b_a->link != ''){
            $link = $b_a->link;
        }
        else{
            $link = 0;
        }
        $ar_pr['id'] = $link;
        $ar_pr['title'] = $b_a->title;
        $ar_pr['icon'] = $b_a->image;
        $ar_pr['left_icon'] = $b_a->left_image;
        $ar_cat['content'][] = $ar_pr;
        $i++;
    }
    $output = json_encode($ar_cat);
    return $output;
};
/*Получаем список категорий можно/нельзя если есть параметр get то выводим список продуктов из полей можно и нельзя для айдишника взятого с get*/
if($res_id == 15420){
    if($_GET['category_id'] == ''){
        $i = 0;
        $ar_cat = array();
        $where = array(
        'parent' => 6565,
        'template' => 21,
        'published' => 1,
        'deleted' => 0
        );
        $resource_tv = $modx->getObject('modResource',15420);
        $array_icons = $resource_tv->getTVValue('yes_no_categories');
        $array_icons = json_decode($array_icons);
        $resources = $modx->getCollection('modResource',$where);
        foreach ($resources as $k => $res) {
          $ar_pr = array(); 
          $id = $res->get('id');
          $ar_pr['id'] = $res->get('id');
          $ar_pr['title'] = $res->get('pagetitle');
          $ic = '';
          foreach($array_icons as $ar_i){
            if($ar_i->link == $id){
                $ic = $ar_i->image;
            }  
          }
          $ar_pr['icon'] = $ic;
          $ar_cat['categories'][] = $ar_pr;
          $i++;
          
        }
        $output = json_encode($ar_cat);
        return $output;
    }
    else{
            $resource = $modx->getObject('modResource',$_GET['category_id']);
            $resource_tv = $modx->getObject('modResource',15420);
            
            if($resource && $resource->parent == 6565){//if else для отсеивания плохих айди
                $mozno = $resource->getTVValue('app_mozhno');
                $nelzya = $resource->getTVValue('app_nelzya');
                $icon_no = $resource_tv->getTVValue('icon_no_api_v1');
                $icon_yes = $resource_tv->getTVValue('icon_yes_api_v1');
                $array_yes = array();
                $array_no = array();
                /*оперируем с текстом*/
                $mozno = str_replace(array('<ul class="mojno">','</ul>','<li>','\r\n', '\r', '\n',';','\r\n\t'),'', $mozno);
                $mozno = str_replace(array('</li>'),'spl2', $mozno);
                $mozno = preg_replace('/\p{Cc}+/u', '', $mozno);
                $array_yes = explode('spl2',$mozno);
                array_pop($array_yes);
                $nelzya = str_replace(array('<ul class="nelzya">','</ul>','<li>','\r\n', '\r', '\n',';','\r\n\t'),'', $nelzya);
                $nelzya = str_replace(array('</li>'),'spl2', $nelzya);
                $nelzya = preg_replace('/\p{Cc}+/u', '', $nelzya);
                $array_no = explode('spl2',$nelzya);
                array_pop($array_no);
                
                $output['icon_allowed'] = $icon_yes;
                $output['icon_forbidden'] = $icon_yes;
                $output['allowed'] = $array_yes;
                $output['forbidden'] = $array_no;
                return json_encode($output);
            }
            else{
                $output['message'] = 'Ошибка при получении данных';
                return json_encode($output);
            }
            
    }
    
    
}
/*Поиск по блюдам можно-нельзя*/
if($res_id == 15422){
    if($_GET['query'] == ''){
        $output['message'] = 'Введите хотя бы одно слово для поиска';
        return json_encode($output);
    }
    else{
        $where = array(
            'parent' => 6565,
            'template' => 21,
            'published' => 1,
            'deleted' => 0
        );
        $resource_tv = $modx->getObject('modResource',15420);
        $resources = $modx->getCollection('modResource',$where);
        $global_yes = array();
        $global_no = array();
        $search_fraze = $_GET['query'];
        /*запрашиваем все блюда и сливаем в два больших можно-нельзя, по ним будет идти поиск*/
        foreach ($resources as $k => $res) {
            $ar_allowed = array();
            $ar_forbidden = array();
            $mozno = $res->getTVValue('app_mozhno');
            $nelzya = $res->getTVValue('app_nelzya');
            $mozno = str_replace(array('<ul class="mojno">','</ul>','<li>','\r\n', '\r', '\n',';','\r\n\t'),'', $mozno);
            $mozno = str_replace(array('</li>'),'spl2', $mozno);
            $mozno = preg_replace('/\p{Cc}+/u', '', $mozno);
            $array_yes = explode('spl2',$mozno);
            array_pop($array_yes);
            $nelzya = str_replace(array('<ul class="nelzya">','</ul>','<li>','\r\n', '\r', '\n',';','\r\n\t'),'', $nelzya);
            $nelzya = str_replace(array('</li>'),'spl2', $nelzya);
            $nelzya = preg_replace('/\p{Cc}+/u', '', $nelzya);
            $array_no = explode('spl2',$nelzya);
            array_pop($array_no);
            $global_yes = array_merge($global_yes, $array_yes);
            $global_no = array_merge($global_no, $array_no);

        }
        $end_array = array();
        $count = 0;
        /*Сам поиск через stripos*/
        foreach($global_yes as $gy){
            $pos = stripos($gy,$search_fraze);
            if($pos !== false){
                $count++;
                $gy_ar['id'] = $count;
                $gy_ar['title'] = $gy;
                $gy_ar['allowed'] = true;
                $end_array['search_result'][] = $gy_ar;
            }
        }
        foreach($global_no as $gn){
            $pos = stripos($gn,$search_fraze);
            if($pos !== false){
                $count++;
                $gn_ar['id'] = $count;
                $gn_ar['title'] = $gn;
                $gn_ar['allowed'] = false;
                $end_array['search_result'][] = $gn_ar;
            }
        }
        if($count > 0){
            $end_array['nothing_found'] = false;
        }
        else{
            $end_array['nothing_found'] = true;
        }
        return json_encode($end_array);
    }
    
}
/*Список категорий рецептов либо список рецептов одной категории с постраничной разбивкой*/
if($res_id == 15425){
    /*все категориипри пустом айди*/
    if($_GET['category_id'] == ''){
        $where = array(
            'parent' => 119,
            'template' => 22,
            'published' => 1,
            'deleted' => 0
        );
        $resource_tv = $modx->getObject('modResource',15425);
        $array_icons = $resource_tv->getTVValue('recept_categories');
        $array_icons = json_decode($array_icons);
        $resources = $modx->getCollection('modResource',$where);
        foreach ($resources as $k => $res) {
          $ar_pr = array(); 
          $id_r = $res->get('id');
          $ar_pr['id'] = $res->get('id');
          $ar_pr['title'] = $res->get('pagetitle');
          $ic = '';
          foreach($array_icons as $ar_i){
            if($ar_i->link == $id_r){
                $ic = $ar_i->image;
            }  
          }
          $ar_pr['icon'] = $ic;
          $ar_cat['categories'][] = $ar_pr;
          
        }
        $output = json_encode($ar_cat);
        return $output;
    } 
    else{
        if($_GET['page'] == ''){
            $page = 0;
        }
        else{
            $page = $_GET['page'];
        }
        $limit = 10;//лимит, можно менять
        $offset = $limit * $page;//пропуск первых n-элементов
        $next_page = $page + 1;//след страница
        /*Ниже получаем буквенное значение категории, в бд оно хранится в видетекста*/
        $resource_cat = $modx->getObject('modResource',$_GET['category_id']);
        $cat_value = $resource_cat->get('pagetitle');//название понадобится для уточнения категории
        //запрос для подсчета общегоколичества
        $where_count = $modx->newQuery('modResource');
        $where_count->leftJoin('modTemplateVarResource', 'TemplateVarResources');
        $where_count->leftJoin('modTemplateVar', 'tv', "tv.id=TemplateVarResources.tmplvarid");
        $where_count->where(array(
            array(
                'tv.name'   => 'recipecategory', // Имя TV
                'TemplateVarResources.value'    => $cat_value,// Значение TV
                'parent' => 119,// Родитель 
                'template' => 6,
                'published' => 1,
                'deleted' => 0,
            )
        ));
        $resources_count = $modx->getCollection('modResource',$where_count);
        $total_count = count($resources_count);//считаем общее и ниже количество страниц
        $pages_count = ceil($total_count/10);
        if($page > ($pages_count - 1)){//исключаем ввод бОльших чисел ля страницы
            $output['message'] = 'Ошибка при получении данных';
            return json_encode($output);
        }
        /*Ниже чистовой запрос с лимитом и офсетом*/
        $where = $modx->newQuery('modResource');
        $where->leftJoin('modTemplateVarResource', 'TemplateVarResources');
        $where->leftJoin('modTemplateVar', 'tv', "tv.id=TemplateVarResources.tmplvarid");
        $where->limit($limit,$offset);// Лимит
        $where->where(array(
            array(
                'tv.name'   => 'recipecategory', // Имя TV
                'TemplateVarResources.value'    => $cat_value,// Значение TV
                'parent' => 119,// Родитель 
                'template' => 6,
                'published' => 1,
                'deleted' => 0,
            )
        ));
        $end_array = array();
        $end_array['current_page'] = $page;
        $end_array['next_page'] = $next_page;
        $end_array['pages_count'] = $pages_count;
        $end_array['recipes_count'] = $total_count;
        $end_array['recipes_per_page'] = $limit;
        $resources = $modx->getCollection('modResource',$where);
        foreach ($resources as $k => $res) {
          $ar_pr = array(); 
          $ar_pr['id'] = $res->get('id');
          $ar_pr['title'] = $res->get('pagetitle');
          $ar_pr['image'] = $res->getTVValue('image');
          $end_array['recipes'][] = $ar_pr;
        }
        return json_encode($end_array);
    }
}
/*Постраничное получение рецептов*/
if($res_id == 15426){
    if($_GET['recipe_id'] == ''){//защита от пустого айди
        $output['message'] = 'Ошибка при получении данных';
        return json_encode($output);
    }
    $end_array = array();
    $recipe_id = $_GET['recipe_id'];//id рецепта
    $res = $modx->getObject('modResource',$recipe_id);
    $title = $res->get('pagetitle');
    $description = $res->get('content');
    $sostav_array = json_decode($res->getTVValue('sostav'));
    $ingredients = array();
    foreach($sostav_array as $sa){//переформировываем json migx в нужную нам структуру
        $array_time['id'] = $sa->MIGX_id;
        $array_time['title'] = $sa->name;
        $array_time['count'] = $sa->value;
        $ingredients[] = $array_time;
    }
    $image = $res->getTVValue('image');
    $end_array['id'] = $recipe_id;
    $end_array['title'] = $title;
    $end_array['image'] = $image;
    $end_array['ingredients'] = $ingredients;
    $end_array['description'] = $description;
    if($title != ''){
        return json_encode($end_array);
    }
    else{//защита от пустых рецептов и некорректных айди
        $output['message'] = 'Ошибка при получении данных';
        return json_encode($output);
    }
    
}
/*Поиск по рецептам*/
if($res_id == 15427){
    if($_GET['query'] == ''){
        $output['message'] = 'Введите хотя бы одно слово для поиска';
        return json_encode($output);
    }
    else{
        $query = $_GET['query'];
        $where = array(
            'parent' => 119,
            'template' => 6,
            'published' => 1,
            'deleted' => 0,
            'pagetitle:LIKE' => '%'.$query.'%'//выгружаем все с заголовком содержащим запрос
        );
        $resources = $modx->getCollection('modResource',$where);
        $search_result = array();
        if(count($resources) == 0){//если ничего не нашлось возвращаем nothing_found
            $search_result['nothing_found'] = true;
            return json_encode($search_result);
        }
        else{
            $search_result['nothing_found'] = false;
        }
        foreach ($resources as $k => $res) {//формируем структуру
          $ar_pr = array(); 
          $ar_pr['id'] = $res->get('id');
          $ar_pr['title'] = $res->get('pagetitle');
          $ar_pr['icon'] = $res->getTVValue('image');
          $search_result['search_result'][] = $ar_pr;
          
        }
        return json_encode($search_result);
    }
}
/*Меню неделька*/
if($res_id == 15428){
    $idweek = 11450;//id документа меню на неделю, пока статичен
    $where = array(
        'parent' => $idweek,
        'template' => 37,
        'published' => 1,
        'deleted' => 0
    );
    $resources = $modx->getCollection('modResource',$where);
    $end_array = array();
    echo '<pre>';
    foreach ($resources as $k => $res) {//формируем структуру
        $ar_pr = array(); 
        $ar_pr['id'] = $res->get('id');
        $ar_pr['title'] = $res->get('description');
        $array_meals_migx = json_decode($res->getTVValue('multiTV_nedelka'));
        foreach($array_meals_migx as $elem){//переделываем json migx с блюдами
            $elem_pr = array();
            /*Если в айди мигикса только цифры то это явно айди требующий получения объекта из modx*/
            if (preg_match("/^([0-9])+$/", $elem->id_bluda)) {//берем данные из объекта
                $res_elem = $modx->getObject('modResource',$elem->id_bluda);
                $elem_pr['id'] = $elem->id_bluda;
                $elem_pr['title'] = $res_elem->get('pagetitle');
                $elem_pr['image'] = $res_elem->getTVValue('image');
                $elem_pr['time_title'] = $elem->time;
            }
            else{//берем данные напрямую из массива мигикс
                $elem_pr['id'] = 0;
                $elem_pr['title'] = $elem->id_bluda;
                $elem_pr['image'] = $elem->image;
                $elem_pr['time_title'] = $elem->time;
            }
            $ar_pr['meals'][] = $elem_pr;
        }

        $end_array['days_of_week'][] = $ar_pr;  
    }
    return json_encode($end_array);
}
/*Статья по айди*/
if($res_id == 15429){
    if($_GET['article_id'] == ''){//защита от пустого айди
        $output['message'] = 'Ошибка при получении данных';
        return json_encode($output);
    }
    else{
        $end_array = array();
        $statya_id = $_GET['article_id'];
        $res_statya = $modx->getObject('modResource',$statya_id);
        if($res_statya == null){
            $output['message'] = 'Ошибка при получении данных';
            return json_encode($output);
        }
        


        $bad_array = getContent($statya_id);
        $bad_array = json_decode($bad_array);
        /*echo '<pre>';
        print_r($bad_array);*/
        $end_array['id'] = $bad_array[0]->id;
        $end_array['title'] = $bad_array[0]->pagetitle;
        $end_array['image'] = $bad_array[0]->tv.image;
        $end_array['content'] = $bad_array[0]->content;
        
        return json_encode($end_array);

    }
}
/*заболевания печени*/
if($res_id == 15432){
    $q = $modx->newQuery('modResource');
    $q->sortby('pagetitle', 'ASC');  
    $q->where(array( 'parent' => 232, 'template:IN' => array(3,4), 'published' => 1, 'deleted' => 0, 'class_key' => 'modDocument'));
    $resources = $modx->getCollection('modResource',$q);
    
    $main_array = array();
    foreach ($resources as $k => $res) {//формируем структуру diseases
        $array_kats = array();
        $array_kats['id'] = $res->get('id');
        $array_kats['title'] = $res->get('pagetitle');
        $where_childs = array(
            'parent' => $res->get('id'),
            'template:IN' => array(3,4),
            'published' => 1,
            'deleted' => 0,
            'class_key' => 'modDocument',
        );
        $childs = $modx->getCollection('modResource',$where_childs);
        $array_childs = array();
        $prom_re = array();
        $first_res  = $modx->getObject('modResource',$res->get('id'));//Первой идет статья задающая название категории
        $prom_re['id'] = $first_res->get('id');
        $prom_re['title'] = $first_res->get('pagetitle');
        $array_childs[] = $prom_re;
        foreach ($childs as $k => $child) {
            $prom_re = array();
            $prom_re['id'] = $child->get('id');
            $prom_re['title'] = $child->get('pagetitle');
            $array_childs[] = $prom_re;
        }
        $array_kats['diseases'] = $array_childs;
        $main_array['categories'][] = $array_kats;
    }
    return json_encode($main_array);
}
/*Поиск по заболеваниям*/
if($res_id == 15433){
    $array_ids = array();
    
    if($_GET['query'] == ''){
        $output['message'] = 'Введите хотя бы одно слово для поиска';
        return json_encode($output);
    }
    else{
        $array_ids = $modx->getChildIds(232,2);
        $query = $_GET['query'];
        $q = $modx->newQuery('modResource');
        $q->sortby('pagetitle', 'ASC');  
        $q->where(array('template:IN' => array(3,4), 'published' => 1, 'deleted' => 0, 'class_key' => 'modDocument','pagetitle:LIKE' => '%'.$query.'%','id:IN' => $array_ids));
        $resources = $modx->getCollection('modResource',$q);
        $end_array = array();
        if(count($resources) == 0){
            $end_array['nothing_found'] = true;
            return json_encode($end_array);
        }
        else{
            $end_array['nothing_found'] = false;
        }
        foreach ($resources as $k => $res) {
            $temp_array = array();
            $temp_array['id'] = $res->get('id');
            $temp_array['title'] = $res->get('pagetitle');
            $end_array['search_result'][] = $temp_array;
        }
        return json_encode($end_array);
    }    
}
/*Как лечить*/
if($res_id == 15434){
    $res  = $modx->getObject('modResource', 15434);
    $opecheni = $res->getTVValue('opecheni_api_v1');
    $opecheni = json_decode($opecheni);
    $end_array = array();
    foreach($opecheni as $article){
        $temp_array = array();
        $temp_array['id'] = $article->link;
        $temp_array['title'] = $article->title;
        $temp_array['description'] = $article->descriptionMulti;
        $temp_array['icon'] = $article->image;
        $end_array['articles'][] = $temp_array;
    }
    return json_encode($end_array);
}
/*О печени*/
if($res_id == 15435){
    $res  = $modx->getObject('modResource', 15435);
    $opecheni = $res->getTVValue('opecheni_api_v1');
    $opecheni = json_decode($opecheni);
    $end_array = array();
    foreach($opecheni as $article){
        $temp_array = array();
        $temp_array['id'] = $article->link;
        $temp_array['title'] = $article->title;
        $temp_array['description'] = $article->descriptionMulti;
        $temp_array['icon'] = $article->image;
        $end_array['articles'][] = $temp_array;
    }
    return json_encode($end_array);
}
