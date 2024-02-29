<?

?>

<script>
    if(window.parent && window.parent.Submissions){
        window.parent.Submissions.keepLastSelection = true;    
        window.parent.Submissions.bbar.doRefresh();
    }else{
        document.write('<center><h1>Thank You</h1></center>')
    }
</script>