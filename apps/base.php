<?php
function R($request,$body = '', $status = 200, $headers = array()){
    return response($request,$body, $status, $headers);
}
function P($request,$config=[
    'orientation'=>'P',
    'unit'=>'mm',
    'format'=>'A4',
    'unicode'=>true,
    'encoding'=>'UTF-8',
    'diskcache'=>false
]){
    return pdf($request,$config);

}
function T($request,$guard = null,$cache = null){
  return token($request,$guard,$cache);
}
function V($request,$template, $vars = []){
    return view($request,$template,$vars);
}
function M($request,$model=null,$app=null,$constructor=[]){
  return model($request,$model,$app,$constructor);
}
function ML($connect=null){
  return mailer($connect);
}
function Q($request,$text,$format = 'png',$outfile = false, $level = 0, $size = 3, $margin = 4,$saveandprint=false){
  return qrcode($request,$text,$format,$outfile, $level, $size, $margin,$saveandprint);
}
function SC($request,$keyvalue = [], $expires = 0, $path = '', $domain = '', $secure = false, $http_only = false){
  return setcookies($request,$keyvalue,$expires,$path,$domain,$secure,$http_only);
}
function GC($request, $key = null, $default = null){
    return getcookies($request,$key,$default);
}
function S($request,$key = null, $default = null){
    return sessions($request,$key,$default);
}
function C($request,$name = 'captcha', $length = 5, $phrase=[], $charset = 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'){
    return captcha($request,$name, $length, $phrase, $charset);
}
function D($request,$file,$download_name=''){
    return download($request,$file,$download_name);
}
function AF($request,$vars){
    return autoForm($request,$vars);
}
function VD(){
    return validator();
}
function X(){
    return xlsx();
}
function SDB($request,...$config){
    return simple_database($request,... $config);
}
function RD($engine='',$type=null,$id=1,$class=null,$clusterclass=null,$config=null){
  return redis($engine,$type,$id,$class,$clusterclass,$config);
}
function DB($engine='',$type=null,$id=1,$class=null,$config=null,$support=null){
    return database($engine,$type,$id,$class,$config,$support);
}
function PY($charset = 'utf-8'){
    return pinyin($charset);
}
?>