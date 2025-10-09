<?php
function get_today(){
  return date('Y-m-d');
}


function conv_date ($db_date) {
  $tmp = explode('-', $db_date);
  return $tmp[2].'.'.$tmp[1].'.'.$tmp[0];
}

function run_latex_env ($texfile) {
#  docker run -v /mnt/Abrechnung:/data dockerd_latex_latex-env:latest pdflatex
  return 1;
}

