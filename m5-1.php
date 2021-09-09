<?php
    //HTMLで常に出てくる変数はいったん空白にしとく:notice-undefinedの対策でもあるよ
    $editNum="";
    $editName="";
    $editComment="";
    $errorMessage = "";
    
    //データベース(BULLETINBOARD)読み込み処理
    $dsn = 'mysql:dbname=tb230312db;host=localhost';
    $user = 'tb-230312';
    $password = 'sJnTsUaGAr';
    $pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    //新規投稿と編集後送信の処理
    if(isset($_POST['send']) === true){
        $postNum = $_POST["postNum"];
        $postName = $_POST["postName"];
        $postComment = $_POST["postComment"];
        $postPass = $_POST["postPass"];
        $date = date("Y/m/d H:i:s");
        if($postName == "" || $postComment == ""){
            $errorMessage = "名前とコメントは入力必須です";
        }else{
            //新規投稿のときは投稿番号欄に何も書いてない
            if($postNum == ""){
                //INSERT句
                $sqlIns = $pdo -> prepare("INSERT INTO BULLETINBOARD (id,name, comment, date, password) VALUES (:id, :postName, :postComment, :date, :postPass)");
                //idカラム取得SQL
                $sqlSel = 'SELECT id FROM BULLETINBOARD';
                $stmt = $pdo->query($sqlSel);
                //投稿番号を配列に格納 [1,2,3,4,...]
                $results = $stmt->fetchAll();
                //$results配列の数の値が投稿番号で最も大きい番号
                $largestId = count($results);
                $id = $largestId + 1;
                $sqlIns -> bindParam(':id', $id, PDO::PARAM_INT);
                $sqlIns -> bindParam(':postName', $postName, PDO::PARAM_STR);
                $sqlIns -> bindParam(':postComment', $postComment, PDO::PARAM_STR);
                $sqlIns -> bindParam(':date', $date ,PDO::PARAM_STR);
                $sqlIns -> bindParam(':postPass', $postPass, PDO::PARAM_STR);
                $sqlIns -> execute();
            //編集時の処理
            }else{
                //UPDATE句
                $sqlUpdate = 'UPDATE BULLETINBOARD SET name = :postName, comment = :postComment, password = :postPass,date = :date WHERE id = :postNum';
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                //パラメーターを代入
                $stmtUpdate -> bindParam(':postName', $postName, PDO::PARAM_STR);
                $stmtUpdate -> bindParam(':postComment', $postComment, PDO::PARAM_STR);
                $stmtUpdate -> bindParam(':date', $date ,PDO::PARAM_STR);
                $stmtUpdate -> bindParam(':postPass', $postPass, PDO::PARAM_STR);
                $stmtUpdate -> bindParam(':postNum', $postNum, PDO::PARAM_INT);
                $stmtUpdate -> execute();
            }
        }
    }elseif(isset($_POST['delete']) === true){
        $deleteNum = $_POST["deleteNum"];
        $deletePass = $_POST["deletePass"];
        //テーブルからidとPassword情報を取得
        $sql = 'SELECT id,password FROM BULLETINBOARD';
        $stmt = $pdo->query($sql);
        //$results配列にはidとpasswordカラムの情報が配列で格納される    
        //$results = [ [1,pass1],[2,pass2][3,pass3]... ]
        $results = $stmt->fetchAll();
        //配列の数=レコードの数=最大の投稿番号
        $largestId = count($results);
        //最大の投稿番号より大きな値が入力されたとき
        if($deleteNum > $largestId || $deleteNum < 0){
            $errorMessage = "存在しない投稿番号です";
        //削除したい番号の入力欄が空白の時エラー出力
        }elseif($deleteNum == ""){
            $errorMessage = "削除したい投稿の番号を入力してください";
        }else{
            //$result配列には削除したい投稿の投稿番号とPassが格納される
            //$result = [投稿番号,パスワード]
            $result = $results[$deleteNum - 1];
            $targetNum = $result[0];
            $targetPass = $result[1];
            //$idList配列には投稿番号が格納 [1,2,3,4,...]
            foreach($results as $row){
                $idList[] = $row[0];
            }
            //$targetPassが空白のとき=削除できない投稿としてエラー出力
            if($targetPass == ""){
                $errorMessage = "この投稿は削除できません";
            //$deletePassと$targetPassが一致したらDELETE句実行
            }elseif($deletePass == $targetPass){
                $sqlDel = 'DELETE FROM BULLETINBOARD WHERE id = :deleteNum';
                $stmt = $pdo->prepare($sqlDel);
                $stmt->bindParam(':deleteNum', $deleteNum, PDO::PARAM_INT);
                $stmt->execute();
                //削除したい投稿の番号より大きい番号は1減らして上書き
                //投稿番号の配列の内容を一つ一つ$idに当てはめる
                foreach($idList as $id){
                    //投稿番号1,2,3..と繰り返し(foreach)削除投稿の番号より大きければ以下処理
                    if($id > $deleteNum){
                        //レコードのidカラムを新しいidに上書きするUPDATE句
                        $sqlUpdate = 'UPDATE BULLETINBOARD SET id = :newId WHERE id = :id';
                        $stmtUp = $pdo->prepare($sqlUpdate);
                        //新しい番号は1小さく
                        $newId = $id - 1;
                        $stmtUp->bindParam(':id', $id, PDO::PARAM_INT);
                        $stmtUp->bindParam(':newId', $newId, PDO::PARAM_INT);
                        $stmtUp->execute();
                    }
                }
            //$deletePassと$targetPassが一致しないときエラー出力
            }else{
                $errorMessage = "パスワードが違います。";
            }
        }
    //編集ボタンが押されたときの処理
    }elseif(isset($_POST['edit']) === true){
        $postEditNum = $_POST["editNum"];
        $postEditPass = $_POST["editPass"];
        $sqlSel = 'SELECT id, name, comment, password FROM BULLETINBOARD';
        $stmt = $pdo->query($sqlSel);
        //$results配列にはpasswordカラムの情報が配列で格納される 
        $results = $stmt->fetchAll();
        $largestId = count($results);
        if($postEditNum > $largestId || $postEditNum < 0){
            $errorMessage = "存在しない投稿番号です";
        }elseif($postEditNum == ""){
            $errorMessage = "編集したい番号を入力してください";
        }else{
            //$results = [ [id,name,comment,password]... ]
            $result = $results[$postEditNum - 1];
            //編集対象の名前-パスワードの情報を取得
            $targetId = $result[0];
            $targetName = $result[1];
            $targetComment = $result[2];
            $targetPass = $result[3];
            if($targetPass == ""){
                $errorMessage = "この投稿は編集できません";
            }elseif($postEditPass == $targetPass){
                $editNum = $targetId;
                $editName = $targetName;
                $editComment = $targetComment;
            }else{
                $errorMessage = "パスワードが違います";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>m5-1.php</title>
</head>
<body>
    <!-- タイトル -->
    <h1 style="font-size:40px">掲示板</h1>
    
    <p style="color:red">
        <?php echo $errorMessage //エラーメッセージ出力 ?>
    </p>
    <form action="m5-1.php" method="POST">
        <p>
            入力フォーム<br><br>
            <input type="number" name="postNum" value="<?php echo $editNum //編集対象の番号 UIでは隠してるよ ?>" hidden>
            <input type="text" name="postName" placeholder="名前" value="<?php echo $editName //編集対象の名前 ?>"><br><br>
            <textarea name="postComment" rows="8" cols="40"><?php echo $editComment //編集対象のコメント ?></textarea><br>
            <input type="password" name="postPass" placeholder="パスワードを入力">
            <input type="submit" name="send" value="送信">
        </p>
        
        <p>
            削除フォーム<br><br>
            <input type="number" name="deleteNum" placeholder="削除したい番号を入力">
            <input type="password" name="deletePass" placeholder="パスワードを入力">
            <input type="submit" name="delete" value="削除">
        </p>
        
        <p>
            編集フォーム<br><br>
            <input type="number" name="editNum" placeholder="編集したい番号を入力">
            <input type="password" name="editPass" placeholder="パスワードを入力">
            <input type="submit" name="edit" value="編集">
        </p>
    </form>
    <hr>
</body>
</html>

<?php
    echo "<table border = '0'>";
    echo "<tr><th>投稿番号</th><th>名前</th><th>コメント</th><th>投稿日時</th></tr>";
    //SELECT句でテーブル情報取得
    $sql = 'SELECT id,name,comment,date,password FROM BULLETINBOARD';
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    foreach ($results as $row){
        //$rowの中にはテーブルのカラム名が入る
        echo "<tr><td>".$row['id']."</td><td>".$row['name']."</td><td>".$row['comment']."</td><td>".$row['date']."</td></tr>";
    }
    echo "</table>";
?>