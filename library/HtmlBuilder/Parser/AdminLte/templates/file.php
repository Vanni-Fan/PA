<?php
$this->css('/dist/plugins/jQuery-File-Upload/css/jquery.fileupload.css');
$this->js('/dist/plugins/jQuery-File-Upload/js/jquery.fileupload.js');

# 拖拽样式
$this->style(/** @lang CSS */ '
.htmlbuild-form-file-over{
    border-color: red !important;
    border-style: dashed;
    border-width: 1px
}
.multiple-file{
    border-style: dashed;
    border-width:1px;
    border-color:#000;
    margin-bottom:5px;
}');

# 通用函数
$this->script(/** @lang JavaScript */ <<<'OUT'
// 全局保存 blob 对象的对象，blobFiles = {"formid":{'file_name_in_the_form':{'file_name':file_obj_1, 'file_name_2':file_obj_2, ...}}}
var blobFiles = {};
var hasCorp = false;
// 获得文件类型字符串
function getFileIcon(file){
    var fa = 'fa ';
    var type = file.type;
    if(/^image/.test(type))      fa += 'fa-file-image-o';
    else if(/^audio/.test(type)) fa += 'fa-file-audio-o';
    else if(/^video/.test(type)) fa += 'fa-file-video-o';
    else if(/^text/.test(type))  fa += 'fa-file-text-o';
    else if(/^application/.test(type)){
        if(/excel|sheet/.test(type))    fa += 'fa-file-excel-o';
        else if(/pdf/.test(type))       fa += 'fa-file-pdf-o';
        else if(/powerpoint/.test(type))fa += 'fa-file-powerpoint-o';
        else if(/word/.test(type))      fa += 'fa-file-word-o';
        else                            fa += 'fa-file-o';
    }
    else fa += 'fa-file-o';
    return fa;
}
// 获得文件尺寸的文字表述
function getFileSize(file){
    var size = file.size;
    var label='0B';
    var base = [
        {label:'GB',pow:1073741824},
        {label:'MB',pow:1048576},
        {label:'KB',pow:1024},
        {label:'B',pow:1}
    ];
    for(var i in base){
        if(size > base[i].pow){
            label = (Math.floor(size/base[i].pow * 100)/100) + base[i].label;
            break;
        }
    }
    return label;
}
// 初始化文件上载控件
function initFileUpload(id, isSingle, canCorp, corpOptions){
    var id = '#' + id;
    $(id + '-file').fileupload(
        { autoUpload:false, replaceFileInput:false, singleFileUploads:isSingle?true:false, dropZone:$(id) }
    ).bind('fileuploadadd', function(e, data){
        $(id).removeClass('htmlbuild-form-file-over');
        var form_id = $(id).parents('form').attr('id');
        var form_item_name = $(id).data('name');
        if(isSingle){
            var file = data.files[0];
            addFiles(file, form_id, form_item_name, 0); // 将文件加入到全局对象中
            $(id + '-text').val(file.name);
            $(id + '-message').text('类型:'+file.type+',大小:'+getFileSize(file));
            if(canCorp){
                var imageBlobUrl = cropperWarp.getImageUrl(file);
                $(id + '-icon').hide().parent().css({
                    padding: 0,
                    width: '35px',
                    backgroundImage: 'url(' + imageBlobUrl + ')',
                    backgroundSize: 'cover'
                }).click(function(){
                    startCorp($(id+'-icon')[0], 0);
                });
            }else{
                $(id + '-icon').removeClass().addClass(getFileIcon(file));
            }
        }else{
            $(id + '-no-file').css('display','none');
            $(id + '-files').css('width','100%').css('display','flex');
            // 清除原来设置
            if(blobFiles[form_id] && blobFiles[form_id][form_item_name]) delete(blobFiles[form_id][form_item_name]);
            for(var i in data.files){
                var file = data.files[i];
                addFiles(file, form_id, form_item_name, i); // 将文件加入到全局对象中
                var tmp = $('#multiple-file-template').clone();
                tmp.removeAttr('id');
                if(canCorp){
                    tmp.find('img').attr('src', cropperWarp.getImageUrl(file));
                    tmp.find('i').remove();
                }else{
                    tmp.find('.edit-btn').remove();
                    tmp.find('img').remove();
                    tmp.find('i').addClass(getFileIcon(file)).css('font-size','40px');
                }
                tmp.find('.filename').text(file.name);
                tmp.css('display','flex');
                tmp.find('.fileinfo').text('类型:'+file.type+', 大小:' +getFileSize(file));
                $(id + '-files').append(tmp);
            }
        }
    }).bind('fileuploaddrop', function(e){
        $(id + '-no-file').css('display','none');
        $(id + '-files').css('width','100%').css('display','flex').html('');
    }).bind('fileuploaddragover',function(e){
        $(id).addClass('htmlbuild-form-file-over');
    });
    $(id).on('dragleave',function(e){
        $(id).removeClass('htmlbuild-form-file-over');
    });
    
    // 打击打开文件选择框
    $(id + '-folder-btn,' + id + '-text,' + id + '-no-file').click(function(e){ $(id + '-file').click(); });
    
    if(canCorp && (corpOptions.width || corpOptions.height)){
        var corpWidth  = corpOptions.width || corpOptions.height;
        var corpHeight = corpOptions.height || corpWidth;
        cropperWarp.setAspectRatio(corpWidth/corpHeight);
    }
}
// 如果有剪裁，修改默认的提交事件，改用Ajax提交
function handForm(form){
    form.submit(function(){
        if(hasCorp[form.attr('id')]){
            var data = new FormData(form[0]);
            data.set('item name in the form', 'replace File', 'the file name');
            console.log(data);
            alert('有裁剪');
            try{
            $.ajax({
                url:form.url,
                type:'post',
                data: data,
                processData:false
            }).then(function(d){
                console.log(d);
            });
            }catch(e){
                console.log(e);
            }
            return false;
        }
        alert('ok');
        return false;
    });
}
// 添加 blob 到全局的 blobFiles 中，将来用于上传
function addFiles(file, form_id, name, index){
    blobFiles[form_id] = blobFiles[form_id] || {};
    blobFiles[form_id][name] = blobFiles[form_id][name] || [];
    blobFiles[form_id][name][index] = file;
}
function startCorp(element, index){
    var current_file_ui = $(element).parents('.htmlbuild-file'); // 找到文件元素位置
    var form_id = current_file_ui.parents('form').attr('id'); // 找到所在 form
    var name = current_file_ui.data('name');
    if(typeof(index) === 'undefined') index = findElementIndex(current_file_ui.find('.edit-btn'), element);
    cropperWarp.setFile(blobFiles[form_id][name][index]).show();
}
function deleteFile(element, index){
    var current_file_ui = $(element).parents('.htmlbuild-file'); // 找到文件元素位置
    var form_id = current_file_ui.parents('form').attr('id'); // 找到所在 form
    var name = current_file_ui.data('name');
    if(typeof(index) === 'undefined') index = findElementIndex(current_file_ui.find('.del-btn'), element);
    $(current_file_ui.find('.htmlbuild-file-item')[index]).remove();
    blobFiles[form_id][name].splice(index,1);
    if(!blobFiles[form_id][name].length){
        delete(blobFiles[form_id][name]);
        current_file_ui.find('div').show();
    }
    if($.isEmptyObject(blobFiles[form_id])) delete(blobFiles[form_id]);
}
function findElementIndex(elements, element){
    for(var i=0; i<elements.length; i++) if(elements[i] === element) return i;
    return -i;
}
OUT
);

# 如果有限制最大宽高，那么就有裁剪
$isSingle = (int)($subtype==='single');
$canCorp  = (int)($corpWidth || $corpHeight);
$corpOptions = '{}';
# 加载裁剪相关的 CSS 、HTML & JS 代码
if($canCorp) {
    $corpOptions = '{width:'.((int)($corpWidth??$corpHeight??100)).',height:'.((int)($corpHeight??$corpWidth??100)).'}';
    $this->css('/dist/plugins/bootstrap-slider/slider.css');
    $this->css('/dist/plugins/cropperjs/cropper.css');
    $this->js('/dist/plugins/bootstrap-slider/bootstrap-slider.js');
    $this->js('/dist/plugins/cropperjs/cropper.js');
    
    # 图片裁剪 CSS
    $this->style(/** @lang CSS */ "
        .cropperWarpDiv{
            display: flex;
            position: fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            justify-content: space-between;
            align-items: center;
            flex-direction: column;
            z-index: 9998;
        }
        .cropperWarpDiv:after{
            content:'';
            width:100%;
            height:100%;
            opacity: 0.5;
            background-color:#000;
            position: fixed;
            top:0;
            left:0;
            z-index: 9999;
        }
        .cropperWarpDiv>.top,.cropperWarpDiv>.bottom{
            color:white;
            z-index:10000;
            min-height: 50px;
            height:50px;
            background-color:#000000b0;
            width:100%;
            text-align: center;
        }
        .cropperWarpDiv>.body{
            z-index:10000;
            /*opacity: .05;*/
            /*padding:20px;*/
            /*flex-grow: 1;*/
            padding: 0 !important;
            height: calc(100% - 100px);
            width:100%;
        }
        .cropperWarpDiv>.top{
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-around;
        }
        .cropperWarpDiv>.bottom{
            padding: 15px;
            /*position: absolute;*/
            bottom: 0;
        }
        .cropperWarpDiv i{
            margin-right:5px;
        }
        .cropperWarpDiv .slider-handle.custom {
            background: transparent none;
        }
        .cropperWarpDiv .slider.slider-horizontal .slider-track-high {
            background-color: #cccccc;
        }
        .cropperWarpDiv .slider-selection{
            background-color: #cccccc;
            background-image: none;
        }
        .cropperWarpDiv .slider-handle.custom::before {
            line-height: 20px;
            font-size: 40px;
            content: '‖';
            color: #fff;
        }
        .cropperWarpDiv .hand{
            cursor: pointer;
        }"
    );

    # 图片裁剪 JS & Form的Ajax提交
    $this->script(/** @lang JavaScript */ "
        var cropperWarp = {
            cropper: new Cropper(document.getElementById('htmlBuilder_image_source')),
            slider: $('#htmlBuilder_slider_bar').slider({step: 1,min: -45,value:0,max: 45,tooltip:'hide'}), // 滑动条
            sliderStatus: {rotate:0,horizontal:false,vertical:false},
            croppedFile:null, // 裁剪器裁剪后的File对象
            currentFile:null, // 当前正在被裁剪的File对象
            setFile:function setFile(file){
                this.currentFile = file;
                this.cropper.replace(this.getImageUrl(file));
                return this;
            },
            doRotate:function doRotate(i){ this.cropper.rotate(i) },
            doHorizontal:function doHorizontal(){
                this.cropper.scale(this.sliderStatus.horizontal ? 1 : -1, this.sliderStatus.vertical ? -1 :1);
                this.sliderStatus.horizontal = !this.sliderStatus.horizontal;
            },
            doVertical:function doVertical(){
                this.cropper.scale(this.sliderStatus.horizontal ? -1 : 1, this.sliderStatus.vertical ? 1 :-1);
                this.sliderStatus.vertical = !this.sliderStatus.vertical;
            },
            doCrop:function doCrop(func,options){
                var that = this;
                hasCorp = true;
                this.cropper.getCroppedCanvas(options || {}).toBlob(function(blob){
                    that.croppedFile = new File([blob], that.currentFile.name, {type:that.currentFile.type})
                    func(that.croppedFile, options);
                    that.hide();
                }, this.currentFile.type);
                return this;
            },
            setImage:function setImage(url){ this.cropper.replace(url); return this; }, // 设置图片
            setAspectRatio:function setAspectRatio(aspectRatio){ this.cropper.setAspectRatio(aspectRatio); return this; }, // 设置高宽比
            doReset:function doReset(){ this.cropper.reset(); return this; },
            getImage:function getImage(){ return this.croppedFile },
            getImageUrl:function getImageUrl(blob){
                return URL.createObjectURL(blob || this.croppedFile);
            },
            show:function show(){ $('.cropperWarpDiv').show(); return this; },
            hide:function hide(){ $('.cropperWarpDiv').hide(); return this; },
            init:function init(){
                var that = this;
                this.slider.on(\"slide\", function(sliderValue) {
                    var newValue = sliderValue.value - that.sliderStatus.rotate;
                    that.doRotate(newValue);
                    that.sliderStatus.rotate = sliderValue.value;
                });
                this.slider.on('slideStart', function () { that.sliderStatus.rotate = 0 });
                this.slider.on('slideStop', function () { that.sliderStatus.rotate = 0; that.slider.slider('setValue',0);});
                return this;
            }
        };
        cropperWarp.init();"
    );

    # 裁剪器
    $this->html(/** @lang HTML */'
        <div class="cropperWarpDiv" style="display: none">
            <div class="top">
                <i onclick="cropperWarp.doRotate(-90)" class="fa fa-rotate-left hand">左转</i>
                <div onclick="cropperWarp.doVertical()" class="hand"><i class="fa fa-exchange" style="transform: rotate(90deg);"></i>垂直翻转</div>
                <i onclick="cropperWarp.doHorizontal()" class="fa fa-exchange hand">水平翻转</i>
                <i onclick="cropperWarp.doReset()" class="fa fa-refresh hand">重置</i>
                <i onclick="cropperWarp.doCrop(function(b){addFiles(b,$(this).parents("form").attr("id"),"name?")})" class="fa fa-crop hand">裁剪</i>
                <i onclick="cropperWarp.hide()" class="fa fa-close hand">取消</i>
            </div>
            <div class="body"><img id="htmlBuilder_image_source" width="100%"></div>
            <div class="bottom"><input id="htmlBuilder_slider_bar" type="text" data-slider-handle="custom"></div>
        </div>'
    );
    
    $this->script("handForm($('#$id').parents('form'));\n");
}

# 初始化JS
$this->script("initFileUpload('$id', $isSingle, $canCorp, $corpOptions);\n");
?>

<?php if($subtype === 'single'){ ?>

<div id="<?=$id?>" data-name="<?=$name?>" class="htmlbuild-file form-group htmlbuild-form <?=$attributes['class']??''?>" style="<?=$visible?'':'display:none;'?>">
    <?php include(__DIR__.'/_label.php'); ?>
    <input accept="<?=$accept?>" id="<?=$id?>-file" name="<?=$name?>" type="file" style="display: none">
    <div class="input-group <?=$labelWidth?('col-sm-'.(12-$labelWidth)):''?>">
        <span class="input-group-addon"><i id="<?=$id?>-icon" class="fa fa-file-o"></i></span>
        <input id="<?=$id?>-text" type="text" class="form-control" placeholder="<?=$placeHolder?>" readonly="true">
        <span class="input-group-addon" id="<?=$id?>-folder-btn"><i class="fa fa-folder-open"></i></span>
    </div>
    <?php if($description){?><span id="<?=$id?>-description" class="help-block pull-left"><?=$description?></span><?php }?>
    <?php if($validators || $statistics){ ?><span id="<?=$id?>-message" class="help-block pull-right"></span><?php } ?>
</div>

<?php }elseif($subtype === 'multiple'){
# 多文件上传的模板样式
$this->html(/** @lang HTML */'
<div id="multiple-file-template" style="padding:10px;display:none;width:100%;" class="htmlbuild-file-item">
    <div style="flex-basis:50px">
        <img src="" style="width:50px; height:50px;float:left;">
        <i></i>
    </div>
    <div style="flex-grow: 1">
        <div class="filename" style="font-weight: bold;padding-bottom:5px;padding-left:5px;padding-right: 5px;">件名文件名.jpg</div>
        <div class="fileinfo" style="padding-left:5px;padding-right: 5px;">Type:image/jpeg, Size:192.1k</div>
    </div>
    <div style="flex-basis: 50px">
        <button type="button" class="btn btn-block btn-primary btn-xs edit-btn" onclick="startCorp(this)">Edit</button>
        <button type="button" class="btn btn-block btn-danger btn-xs del-btn" onclick="deleteFile(this)">Remove</button>
    </div>
</div>'
);
?>
<div id="<?=$id?>" data-name="<?=$name?>" class="htmlbuild-file multiple-file <?=$attributes['class']??''?>" style="display:flex;flex-wrap:wrap;">
    <input accept="<?=$accept?>" id="<?=$id?>-file" name="<?=$name?>" multiple="multiple" type="file" style="display: none">
    <div id="<?=$id?>-no-file" style="width:100%;display: flex;align-items: center;justify-content: center;transition: all 1s;">
        <i class="fa fa-cloud-upload" style="font-size: 50px;padding: 10px;"></i>
        <span><?=$label?:'Drag & Drop a File'?></span>
    </div>
    <div id="<?=$id?>-files" style="width:100%;transition: width 1s;display:none;flex-wrap: wrap;"></div>
</div>
<?php } ?>