
<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Headers: *');
// header('Access-Control-Allow-Credentials: true');

$host = 'localhost';
$user = 'root';
$password = 'password';
$database = 'posts';


$mysqli = new mysqli($host,$user,$password,$database);

if (!$mysqli){
	$result = ['code' => 10,'status' => 500,'message' => 'Request Error'];
	echo json_encode($result);
	exit();
}

//HELPERS
function ok($response=['code' => 0,'status' => 200,'message' => 'ok']){
	echo json_encode($response);
}

function checkRequest($request){
	global $mysqli;
	$error = ['code' => 10,'status' => 500,'message' => 'Request Error'];
	$result = $mysqli->query($request);
	if (!$result){
		echo json_encode($error);
		exit();
	}
	return $result;
}
function throwError($bool,$array){
	if ($bool){
		echo json_encode($array);
		exit();
	}
}

function read($result){
	$list=[];
	for ($i=0; $i < $result->num_rows; $i++) { 
		$result->data_seek($i);
		$string = $result->fetch_assoc();
		$list[] = $string;
	}
	return $list;
}

function register($data){
	extract($data);
	$result = checkRequest("select * from users where user='$user_name';");
	throwError($result->num_rows>0,['code' => 15,'message' => 'This name already exists']);
	checkRequest("insert users(user,password) values('$user_name','$password')");
	$result = checkRequest("select * from users where user='$user_name';");
	ok(['code' => 0,'status' => 200,'message' => 'ok','data' => $result->fetch_assoc()]);
}

function login($data){
	extract($data);
	$result = checkRequest("select * from users where user='$user_name';");
	throwError($result->num_rows==0,['code' => 15,'message' => 'You not registred']);
	$result->data_seek(0);
	$result_array = $result->fetch_assoc();
	throwError($result_array['password']!=$password,['code' => 20,'message' => 'Wrong password']);
	ok(['code' => 0,'status' => 200,'message' => 'ok','data' => $result_array]);
}

function getPosts($data){
	extract($data);
	if ($tag==''){
		$result = checkRequest("select * from posts order by id desc limit $page_count");
		$TOTAL_COUNT = checkRequest("select count(*) from posts;")->fetch_assoc();
		ok(['code' => 0,'status' => 200,'message' => 'ok','data' => read($result),'total_count' => 
		$TOTAL_COUNT]);
		exit();
	}
	// echo "select * from posts order by id desc limit $page_count where JSON_CONTAINS(tags,'\"$tag\"','$')";
	// exit();
	$result = checkRequest("select * from posts where JSON_CONTAINS(tags,'\"$tag\"','$') order by id desc limit $page_count");

	ok(['code' => 0,'status' => 200,'message' => 'ok','data' => read($result),'total_count' => 
		$TOTAL_COUNT]);
}
//if response->num_rows<=$page_count

function getOnePost($data){
	extract($data);
	$result = checkRequest("select * from posts where id=$id;");
	throwError($result->num_rows==0,['code' => 10,'status' => 404,'message' => 'Card doesnt exist']);
	ok(['code' => 0,'status' => 200,'message' => 'ok','data' => $result->fetch_assoc()]);
}
function getMyPosts($data){
	extract($data);
	$result = checkRequest("select * from posts where user='$user_name';");
	ok(['code' => 0,'status' => 200,'message' => 'ok','data' => read($result)]);
}
function addPost($file_name,$tmp_name,$data){
	move_uploaded_file($tmp_name,"backgrounds/$file_name");
	extract($data);
	$jstags = json_encode($tags);
	$jsimages = json_encode($images);
	$jsvideos = json_encode($videos);
	checkRequest("insert posts(user,post_text,title,tags,post_image,images,videos) values('$user_name','$description','$title','$jstags','$file_name','$jsimages','$jsvideos');");
	ok();
}
function changePost($file_name,$tmp_name,$data){
	extract($data['data']);
	$id = $data['id'];
	$jstags = json_encode($tags);
	if ($file_name=='POST_admin'){
		checkRequest("update posts set post_text = '$description', title = '$title',tags = '$jstags' where id = '$id';");
		ok();
		exit();

	}
	
	move_uploaded_file($tmp_name,"backgrounds/$file_name");
	$newTags = json_encode($tags);
	checkRequest("update posts set post_text = '$description', title = '$title',tags = '$jstags',post_image = '$file_name' where id = '$id';");
	ok();
}
function addTagImage($file_name,$tmp_name,$data){
	$tag_name = $data['tag_name'];
	move_uploaded_file($tmp_name,"tags_backgrounds/$file_name");
	checkRequest("insert tags(name,image) values('$tag_name','$file_name')");
	ok();

}
function getTagBackground($data){
	$value = $data['value'];
	$result = checkRequest("select image from tags where name='$value';");
	ok(['code' => 0,'status' => 200,'message' => 'ok','data' => $result->fetch_assoc()]);
}
?>
