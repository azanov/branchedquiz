jsPlumb.ready(function () {

    var canvas = document.getElementById("questionCanvas");
    var questions = jsPlumb.getSelector(".js-question-canvas .activity");


    // setup some defaults for jsPlumb.
    var instance = jsPlumb.getInstance({
        Endpoint: ["Dot", {radius: 2}],
        Connector: "StateMachine",
        HoverPaintStyle: {stroke: "#1e8151", strokeWidth: 2 },
        ConnectionOverlays: [
            [ "Arrow", {
                location: 1,
                id: "arrow",
                length: 14,
                foldback: 0.8
            } ],
            // [ "Label", { label: "FOO", id: "label", cssClass: "aLabel" }]
        ],
        Container: "questionCanvas"
    });

    instance.registerConnectionType("basic", { anchor:"Continuous", connector:"StateMachine" });

    window.jsp = instance;




    //
    // initialise element as connection targets and source.
    //
    var initNode = function(el) {

        // initialise draggable elements.
        instance.draggable(el);

        instance.makeSource(el, {
            filter: ".ep",
            anchor: "Continuous",
            connectorStyle: { stroke: "#5c96bc", strokeWidth: 2, outlineStroke: "transparent", outlineWidth: 4 },
            connectionType:"basic",
            extract:{
                "action":"the-action"
            },
            // maxConnections: 2,
            // onMaxConnections: function (info, e) {
            //     alert("Maximum connections (" + info.maxConnections + ") reached");
            // }
        });

        instance.makeTarget(el, {
            dropOptions: { hoverClass: "dragHover" },
            anchor: "Continuous"
        });

        // this is not part of the core demo functionality; it is a means for the Toolkit edition's wrapped
        // version of this demo to find out about new nodes being added.
        //
        instance.fire("jsPlumbDemoNodeAdded", el);
    };

    // suspend drawing and initialise.
    instance.batch(function () {

        var slots = {};

        for (var i = 0; i < questions.length; i++) {
            initNode(questions[i], true);
            var slot = Number(questions[i].dataset.slot);
            slots[questions[i].dataset.slot] = questions[i].id;
            questions[i].style.left = '30px';
            questions[i].style.top = 30 * slot + 50 * (slot - 1) + 'px';
        }

        for (var i = 0; i < questions.length; i++) {
            var slot = Number(questions[i].dataset.slot) + 1;
            if (slots[slot])
                instance.connect({ source: questions[i].id, target: slots[slot], type:"basic" });
        }


    });

    jsPlumb.fire("jsPlumbDemoLoaded", instance);

});