<?php

FormRow('��� �������',$site->Edit('name', '', false, 'style="width: 200px;"'));
FormRow('���������� �����',$site->Edit('cols', '', false, 'style="width: 50px;" title="������� ���� ���������� �������"'));
AddForm('<form action="'.$config['admin_file'].'?exe=fdbadmin&a=newtable" method="post">', $site->Submit('�����','title="������� � ����. ���� �������� �������."'));

?>