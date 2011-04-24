<?php

FormRow('Имя таблицы',$site->Edit('name', '', false, 'style="width: 200px;"'));
FormRow('Количество полей',$site->Edit('cols', '', false, 'style="width: 50px;" title="Введите сюда количество колонок"'));
AddForm('<form action="'.$config['admin_file'].'?exe=fdbadmin&a=newtable" method="post">', $site->Submit('Далее','title="Перейти к след. шагу создания таблицы."'));

?>