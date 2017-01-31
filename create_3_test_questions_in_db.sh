#!/bin/sh

echo "enter user to access db"
read user

echo "enter password for chosen user"
read -s pw

echo "enter moodle database name (default is 'moodle')"
read db

mysql -u$user -p$pw $db -e "insert into mdl_quiz_node values ('1','1')"
mysql -u$user -p$pw $db -e "insert into mdl_quiz_node values ('2','2')"
mysql -u$user -p$pw $db -e "insert into mdl_quiz_node values ('3','3')"
mysql -u$user -p$pw $db -e "insert into mdl_quiz_node values ('4','4')"

mysql -u$user -p$pw $db -e "insert into mdl_quiz_edge values ('1','2','0','question 1 was false','1')"
mysql -u$user -p$pw $db -e "insert into mdl_quiz_edge values ('2','3','1','question 1 was true','1')"
mysql -u$user -p$pw $db -e "insert into mdl_quiz_edge values ('3','4','-1','q1 = t, q3 = x','2')"
mysql -u$user -p$pw $db -e "insert into mdl_quiz_edge values ('4','4','-1','q1 = f, q2 = x','3')"
#edges 3 and 4 should jump to question 4, ignoring earned points in current quesiton (=> points for that path = -1)
