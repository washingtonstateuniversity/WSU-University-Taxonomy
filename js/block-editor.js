window.addEventListener("load", () => {
  var editor = wp.data.dispatch("core/edit-post");
  var taxonomies = document
    .getElementById("wsuwp-taxonomies__metabox-taxonomies")
    .innerText.split(",");

  if (taxonomies && taxonomies.length > 0) {
    for (var i = 0; i < taxonomies.length; i++) {
      var taxonomy = taxonomies[i];
      editor.removeEditorPanel("taxonomy-panel-" + taxonomy);
    }
  }

  //   var editor = wp.data.dispatch("core/edit-post");
  //   var taxonomies = wp.data.select("core").getTaxonomies();

  //   if (taxonomies && taxonomies.length > 0) {
  //     for (var i = 0; i < taxonomies.length; i++) {
  //       var taxonomy = taxonomies[i];
  //       var slug = taxonomy.slug;
  //       editor.removeEditorPanel("taxonomy-panel-" + slug);
  //     }
  //   }

  //   var editor = wp.data.dispatch("core/edit-post");
  //   editor.removeEditorPanel("taxonomy-panel-category");
  //   editor.removeEditorPanel("taxonomy-panel-post_tag");
  //   editor.removeEditorPanel("taxonomy-panel-wsuwp_university_category");
  //   editor.removeEditorPanel("taxonomy-panel-wsuwp_university_location");
  //   editor.removeEditorPanel("taxonomy-panel-wsuwp_university_org");
});
