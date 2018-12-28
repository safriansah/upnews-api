<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';
use Restserver\Libraries\REST_Controller;

class Berita extends REST_Controller {

    function __construct($config = 'rest') {
        parent::__construct($config);
        $this->URL=["http://pers-upn.com/wp-json/wp/v2/posts", "https://www.upnjatim.ac.id/wp-json/wp/v2/posts"];
        //, "http://ilkom.upnjatim.ac.id/wp-json/wp/v2/posts"
        $this->kata=["yang", "di", "ini", "bahwa", "dalam", "dan", "dengan", "ada", "hal", "beberapa", "nama", "tidak", "itu", "dapat", "dari", "tinggi", "agar", "harus", "untuk", "saya", "orang", "ia", "pada", "kepada", "juga", "tersebut", "lebih", "saat", "ketika", "hanya" , "bisa" , "kami" , "mereka" , "hingga" , "sudah" , "belum" , "makin" ,"maka" , "para" , "telah" , "kembali" , "ke" , "dia" , "lain" , "jadi" ];
        $this->load->library('curl');
        //$this->load->database();
    }
    
    function bersihkan($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }
    
    function sortir($array, $order){
        if(sizeof($array)>1){
            $sortArray = array();
            foreach($array as $item){
                foreach($item as $key=>$value){
                    if(!isset($sortArray[$key])){
                        $sortArray[$key] = array();
                    }
                    $sortArray[$key][] = $value;
                }
            }
            array_multisort($sortArray[$order],SORT_DESC,$array);
        }
        return $array;
    }
    
    function kurangi($array, $jumlah){
        $hasil=array();
        if(sizeof($array)>$jumlah) for($a=0; $a<$jumlah; $a++) array_push($hasil, $array[$a]);
        else array_push($hasil, $array);
        return $hasil;
    }
    
    function hilangkan($array, $kata){
        $hasil=array();
        if(sizeof($array)>0){
            foreach($array as $item){
                $sama=0;
                foreach($kata as $k){
                    if($item==$k) $sama++;
                    if($sama>0) break;
                }
                if($sama==0) array_push($hasil, $item);
            }
        }
        else array_push($hasil, $array);
        return $hasil;
    }
    
    function cekkata($string, $kata){
        $hasil=false;    
        if(strpos(strtolower($string), strtolower($kata)) !== false) {
            $hasil=true;
        }
        return $hasil;
    }
    
    function gettag($konten){
        $sorttag=array();
        $konten=$this->hilangkan(explode('-', $this->bersihkan(strtolower(strip_tags($konten)))), $this->kata);
        $tags=array_unique($konten);
        foreach($tags as $t){
            $muncul=0;
            foreach($konten as $k){
                if($k==$t) $muncul++;
            }
            $tag=array(
                'nama'=>$t,
                'jumlah'=>$muncul
            );
            array_push($sorttag, $tag);
        }
        $sorttag=$this->sortir($sorttag, "jumlah");
        $sorttag=$this->kurangi($sorttag, 4);
        return $sorttag;
    }

    function index_get() {
        $id = $this->get('id');
        $cari=$this->get('konten');
        $halaman=$this->get('halaman');
        $judul=$this->get('judul');
        $gettag=$this->get('tag');
        if(isset($gettag)) $gettag=explode(' ', $gettag);
        $hasil= array();
        
        if(isset($id)){
            foreach ($this->URL as $u){
                $d = json_decode($this->curl->simple_get($u."/".$id."?_embed"));
                if (!empty($d)){
                    $sorttag=$this->gettag($d->content->rendered);
                    $tags=array();
                    foreach($sorttag as $st){
                        if(isset($st['nama'])){
                            array_push($tags, $st['nama']);
                        }
                    }
                    //foreach($sorttag as $st) $tags=$st->nama;
                    if(isset($d->_embedded->{'wp:featuredmedia'}[0]->source_url))
                        $hasil=array(
                            'id'=>$d->id,
                            'tanggal'=>$d->date,
                            'judul'=>$d->title->rendered,
                            'konten'=>$d->content->rendered,
                            'gambar'=>$d->_embedded->{'wp:featuredmedia'}[0]->source_url,
                            'link'=>$d->link,
                            'tag'=>$tags
                        );
                    else
                        $hasil=array(
                            'id'=>$d->id,
                            'tanggal'=>$d->date,
                            'judul'=>$d->title->rendered,
                            'konten'=>$d->content->rendered,
                            'gambar'=>null,
                            'link'=>$d->link,
                            'tag'=>$tags
                        );
                }
            }
        }
        else{
            foreach ($this->URL as $u){
                $url=$u."?_embed";
                if(isset($cari)) $url=$u."?_embed&search=".$cari;
                if(isset($halaman)) $url.="&page=".$halaman;
                $data = json_decode($this->curl->simple_get($url));
                if (!empty($data)){
                    foreach ($data as $d) {
                        if(isset($judul)) if(!$this->cekkata($d->title->rendered, $judul)) continue;
                        $cektag=0;
                        $sorttag=$this->gettag($d->content->rendered);
                        $tags=array();
                        foreach($sorttag as $st){
                            if(isset($st['nama'])){
                                array_push($tags, $st['nama']);
                                if(isset($gettag)){
                                    foreach($gettag as $gt){
                                        if($this->cekkata($st['nama'], $gt)) $cektag++;
                                    }
                                }
                            }
                        }
                        if(isset($gettag)) if($cektag<1) continue;
                        if(isset($d->_embedded->{'wp:featuredmedia'}[0]->source_url))
                        $berita=array(
                            'id'=>$d->id,
                            'tanggal'=>$d->date,
                            'judul'=>$d->title->rendered,
                            'konten'=>$d->content->rendered,
                            'gambar'=>$d->_embedded->{'wp:featuredmedia'}[0]->source_url,
                            'link'=>$d->link,
                            'tag'=>$tags
                        );
                        else
                        $berita=array(
                            'id'=>$d->id,
                            'tanggal'=>$d->date,
                            'judul'=>$d->title->rendered,
                            'konten'=>$d->content->rendered,
                            'gambar'=>null,
                            'link'=>$d->link,
                            'tag'=>$tags
                        );
                        array_push($hasil, $berita);
                    }
                }
            }    
            $hasil=$this->sortir($hasil, "tanggal");
        }
              
        if (!empty($hasil)) $this->response($hasil, 200);
        else
        {
            $this->response([
                'status' => FALSE,
                'message' => 'Data berita tidak ditemukan'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

}
?>
