
$(document).ready(function() {

    /* Изменение типа задачи
     * route('parsing-tasks/create')
     */
    $(".task_type_select").on("change", function(){
        switch ($(this).val()){
            case ('1'):
                $(".task_query_div").css("display","block");
                $(".site_list_div").css("display","none");
                break;
            case ('2'):
                $(".site_list_div").css("display","block");
                $(".task_query_div").css("display","none");
                break;
        }
    });
    //Изменение типа задачи

   /* Обработка изменения чекбокса Рассылать сразу
    * route('parsing-tasks/create')
    */
    $(".form-check-input").on("change", function(){
        if($(this).prop("checked") == true){
            $(".send_directly_div").css("display","block");
        }else{
            $(".send_directly_div").css("display","none");
        }
    });
    //Обработка изменения чекбокса Рассылать сразу

});
