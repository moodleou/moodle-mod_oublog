M.mod_oublog = M.mod_oublog || {};
M.mod_oublog.tagselector = {
    /**
     * Initialise the tag selector.
     *
     * @method init
     * @param {String} fieldId id of text field/area to make autocomplete
     * @param {Array} tags Array of tag objects (tag,count,label properties)
     */
    init: function(fieldId, tags) {
        var inputNode = Y.one('form #' + fieldId);
        if (tags && typeof tags === 'object') {
            // Convert object into array.
            var tags2 = [];
            for (var key in tags) {
                tags2.push(tags[key]);
            }
            tags = tags2;
        }
        if (inputNode) {
            inputNode.plug(Y.Plugin.AutoComplete, {
                minQueryLength: 0,
                queryDelay: 100,
                queryDelimiter: ',',
                allowTrailingDelimiter: true,
                source: tags,
                width: 'auto',
                scrollIntoView: true,
                circular: false,
                resultTextLocator: 'tag',
                resultHighlighter: 'startsWith',

                // Chain together a startsWith filter followed by a custom
                // result filter
                // that only displays tags that haven't already been selected.
                resultFilters: ['startsWith', function(query, results) {
                    // Split the current input value into an array based on
                    // comma delimiters.
                    var selected = '';
                    var lastComma = inputNode.get('value').lastIndexOf(',');
                    if (lastComma > 0) {
                        // Ignore tag currently being typed.
                        selected = inputNode.get('value').substring(0, lastComma).split(/\s*,\s*/);
                    }

                    // Convert the array into a hash for faster lookups.
                    selected = Y.Array.hash(selected);

                    // Filter out any results that are already selected, then
                    // return the
                    // array of filtered results.
                    var newResults = Y.Array.filter(results, function(result) {
                        return !selected.hasOwnProperty(result.text);
                    });
                    // Always sort by tag text to ensure correct.
                    function compareResults(a, b) {
                        if (a.raw.tag < b.raw.tag) {
                            return -1;
                        }
                        if (a.raw.tag > b.raw.tag) {
                            return 1;
                        }
                        return 0;
                    }
                    return newResults.sort(compareResults);
                }],
                // Custom result formatter to show tag info.
                resultFormatter: function(query, results) {
                  return Y.Array.map(results, function(result) {
                      var out = '<div class="tagselector_result"><span class="tagselector_result_title">' +
                              result.highlighted + '</span>';
                      if (result.raw.label) {
                          out += ' <span class="tagselector_result_info tagselector_result_info_label">' +
                              result.raw.label + '</span>';
                      }
                      out += ' <span class="tagselector_result_info">' +
                              M.util.get_string('numposts', 'oublog', result.raw.count) +
                              '</span>' + '</div>';
                    return out;
                  });
                }
            });

            // When the input node receives focus, send an empty query to
            // display the full list of tag suggestions.
            inputNode.on('focus', function() {
                inputNode.ac.sendRequest('');
            });

            // After a tag is selected, send an empty query to update the list of tags.
            inputNode.ac.after('select', function(e) {
                // On select the browser (chrome) is scrolled to input node so you can't see list.
                // See https://github.com/yui/yui3/issues/958
                // Work-around: scroll down by (hard-coded) list height + text area height.
                if (Y.UA.chrome) {
                    window.scrollBy(0, parseInt(inputNode.getStyle('height')) + 200);
                }
                // Send the query on the next tick to ensure that the input node's blur
                // handler doesn't hide the result list right after we show it.
                setTimeout(function() {
                    inputNode.ac.sendRequest('');
                    inputNode.ac.show();
                }, 1);
            });

        }
    }
};
