var operationhope;
(function (operationhope) {
    var CoachingSessionForm;
    (function (CoachingSessionForm) {

            var CreateForm = 1;
        var UpdateForm = 2;
        let executionContext;
        let goalIds = [];
        let workPlanIds = [];
        var isValid = false;

        /// Event Handlers
        function onLoad(context) {
            executionContext = context.getFormContext();
            var formType = executionContext.ui.getFormType();
            var formContext = context.getFormContext();
            SetConcatenatedName(context);
            FilterAllCoachingSessionSubgrid(context);
            // SetContactToEventValue(context);
            filterGoalLookup(context);
            filterWorkPlanLookup(context);
            MakeWorkplanRequired(context);
            handleButton(context);
            // if (formType == UpdateForm) {
            //     hideNotesSection(context);
            // }
            hideEmailFromMethodOfContact(formContext);
            if (formType == CreateForm) {
                hideNotesHTMLsection(context);
            }
        }
        CoachingSessionForm.onLoad = onLoad;

        function onSave(context) {
            isValid = false;
            ValidateForm(context);

            if (!isValid) {
                context.getEventArgs().preventDefault();
            }

        }
        CoachingSessionForm.onSave = onSave;

            // function hideNotesSection(context) {
            //     debugger;
            //     let formContext = context.getFormContext();
            //     let notesSection = formContext.ui.tabs.get("General").sections.get("noteSection");
            //     if(CheckIfIsAPastEvent(formContext)){
            //         if (notesSection) {
            //             notesSection.setVisible(false);
            //         }
            //     }
            // }
            function hideNotesHTMLsection(context) {
                debugger;
                let formContext = context.getFormContext();
                let notesSection = formContext.ui.tabs.get("General").sections.get("General_section_5");
                    if (notesSection) {
                        notesSection.setVisible(false);
                    }
                
            }
        function CheckIfIsAPastEvent(formContext) {
            debugger;
            if (!operationhope.CrmHelper.UserRoleExists(['System Administrator', 'Operation HOPE HRI Admin', 'Operation Hope HRI Admin -N'])) {
                var attDate = formContext.getAttribute("hope_eventstartdate");
                if (attDate) {
                    var eventDate = attDate.getValue();
                    var currentDate = new Date();
        
                    if (eventDate < currentDate) {
                        var monthDiff = (currentDate.getFullYear() - eventDate.getFullYear()) * 12
                                        + (currentDate.getMonth() - eventDate.getMonth());
        
                        if ((monthDiff == 1 && currentDate.getDate() > 10) || monthDiff > 1) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }        

        function ValidateForm(context) {
            var formContext = context.getFormContext();
            var isPastEvent = CheckIfIsAPastEvent(formContext);
            if(isPastEvent){
                formContext.ui.setFormNotification("The selected date is 1 month and 10 days past the allowed time frame.", "ERROR");
            } else {
                isValid = true;
            }
        }

         function handleButton(executionContext) {
            const addCoachingSessionBtn = window.top.document.querySelector('[id^=hope_coachingsession\\|NoRelationship\\|SubGridStandard\\|Mscrm\\.SubGrid\\.hope_coachingsession\\.NewRecord0id-]');
            if (addCoachingSessionBtn) addCoachingSessionBtn.style.display = 'none';

        }
        
        // Create a MutationObserver to watch for changes in the DOM
        const observer = new MutationObserver(() => {
              // Handle the buttons if they are found
            handleButton();
            // If either button is found and hidden, stop observing
            if (document.querySelector('[id^=hope_coachingsession\\|NoRelationship\\|SubGridStandard\\|Mscrm\\.SubGrid\\.hope_coachingsession\\.NewRecord0id-]')) {
                observer.disconnect();
            }
        });
         // Start observing the document body for changes
        observer.observe(window.top.document.body, { childList: true, subtree: true });
        // Initial call to handle buttons in case they are already present
        handleButton();
        CoachingSessionForm.handleButton = handleButton;

        function FilterAllCoachingSessionSubgrid(context) {
            formContext = context.getFormContext();
            var contactId = formContext.getAttribute("hope_contacttoevent").getValue();
           
            if (contactId != null) {
                var filterXml = "<filter type=\"and\">" +
                    "<condition attribute=\"hope_contacttoevent\" operator=\"eq\" uitype=\"contact\" value=\"" + contactId[0].id.replace("{", "").replace("}", "") + "\" />" +
                    "</filter>";
                var subgrid = Xrm.Page.getControl("Subgrid_All_Sessions");
                if (subgrid) {
                    subgrid.setFilterXml(filterXml);
                    subgrid.refresh();
                }
            }
        }
        
        // async function SetContactToEventValue(context) {
        //     let formContext = context.getFormContext();
        //     let contactAttr = formContext.getAttribute("hope_contacttoevent");
        //     let enrollmentid = formContext.getAttribute("hope_programenrollment").getValue();
        //     let enrollment = await operationhope.CrmHelper.RetrieveRecord(enrollmentid[0].entityType, enrollmentid[0].id.replace("{", "").replace("}", ""), "_hope_contact_value");
        //     if(enrollment._hope_contact_value){
        //         let contact = enrollment["_hope_contact_value@OData.Community.Display.V1.FormattedValue"];
        //         let contactid = enrollment._hope_contact_value;
        //         let contactlookup = [];
        //         contactlookup[0] = {};
        //         contactlookup[0].id = contactid;
        //         contactlookup[0].entityType = "contact";
        //         contactlookup[0].name = contact;
        //         contactAttr.setValue(contactlookup);
        //     }
        // }

        async function SetConcatenatedName(context) {
            let formContext = context.getFormContext();
            let concatenatedName = formContext.getAttribute("hope_name");
            let contactId = formContext.getAttribute("hope_contacttoevent").getValue();
            let coachId = formContext.getAttribute("ownerid").getValue();
            if(contactId && coachId){

                let coach = await operationhope.CrmHelper.RetrieveRecord("systemuser", coachId[0].id.replace("{", "").replace("}", ""), "fullname");
                let contact = await operationhope.CrmHelper.RetrieveRecord("contact", contactId[0].id.replace("{", "").replace("}", ""), "fullname");

                let coachName = coach.fullname;
                if (coachName.startsWith("# ")) {
                    coachName = coachName.slice(2).trim();
                }

                concatenatedName.setValue("Coaching Session between " + coachName +" and " + contact.fullname );
            }

        }
        CoachingSessionForm.SetConcatenatedName = SetConcatenatedName;

        function MakeWorkplanRequired(context) {
            let formContext = context.getFormContext();
            let workPlanAttr = formContext.getAttribute("hope_workplan");
            let goalAttr = formContext.getAttribute("hope_goal");
            let statusReason = formContext.getAttribute("statuscode").getValue();
            if(goalAttr && workPlanAttr && statusReason == 368080003){
                operationhope.FormHelper.SetRequiredLevel(goalAttr, "required");
                operationhope.FormHelper.SetRequiredLevel(workPlanAttr, "required");
            } else {
                operationhope.FormHelper.SetRequiredLevel(goalAttr, "none");
                operationhope.FormHelper.SetRequiredLevel(workPlanAttr, "none");
            }
            
        }
        CoachingSessionForm.MakeWorkplanRequired = MakeWorkplanRequired;

        async function filterGoalLookup(context) {
            let formContext = context.getFormContext();
            await fetchGoalBasedOnEnrolledProgram(context);
            formContext.getControl("hope_goal").addPreSearch(filterGoalBasedOnEnrolledProgram);
        }

        async function filterWorkPlanLookup(context) {
            let formContext = context.getFormContext();
            let fieldControl = formContext.getControl("hope_workplan");
            let goal = formContext.getAttribute("hope_goal").getValue();
            if (fieldControl && goal) {
                fieldControl.setDisabled(false);
                await fetchWorkPlanBasedOnGoal(context);
                formContext.getControl("hope_workplan").addPreSearch(filterWorkPlanBasedOnGoal);
            } else {
                fieldControl.setDisabled(true);
            }
        }
        CoachingSessionForm.filterWorkPlanLookup = filterWorkPlanLookup;

        async function fetchGoalBasedOnEnrolledProgram(context) {

            let formContext = context.getFormContext();

            let enrollmentid = formContext.getAttribute("hope_programenrollment").getValue();
            let enrollment = await operationhope.CrmHelper.RetrieveRecord(enrollmentid[0].entityType, enrollmentid[0].id.replace("{", "").replace("}", ""), "_hope_program_value");

            if(enrollment._hope_program_value && enrollmentid[0]){
                let fetchXml = `
                    <fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="true">
                    <entity name="hope_goal">
                        <attribute name="hope_goalid" />
                        <attribute name="hope_name" />
                        <attribute name="createdon" />
                        <order attribute="hope_name" descending="false" />
                        <link-entity name="hope_programgoal" from="hope_goal" to="hope_goalid" link-type="inner" alias="aj">
                        <filter type="and">
                            <condition attribute="hope_program" operator="eq" uitype="hope_program" value="{${enrollment._hope_program_value}}" />
                        </filter>
                        <link-entity name="hope_assignedworkplan" from="hope_goal" to="hope_programgoalid" link-type="inner" alias="ak">
                            <link-entity name="hope_programenrollmentassignedgoals" from="hope_assignedworkplan" to="hope_assignedworkplanid" link-type="inner" alias="al">
                            <filter type="and">
                                <condition attribute="hope_programenrollment" operator="eq" uitype="hope_programenrollment" value="{${enrollmentid[0].id.replace("{", "").replace("}", "")}}" />
                            </filter>
                            </link-entity>
                        </link-entity>
                        </link-entity>
                    </entity>
                    </fetch>`;

                var relatedGoals = await Xrm.WebApi.retrieveMultipleRecords("hope_goal", "?fetchXml=" + encodeURIComponent(fetchXml));

                goalIds = [];

                if(relatedGoals.entities){
                    relatedGoals.entities.forEach(function(entity) {
                        goalIds.push(entity.hope_goalid);
                    });
                }
            }
            
        }

        async function fetchWorkPlanBasedOnGoal(context) {

            let formContext = context.getFormContext();

            let enrollmentid = formContext.getAttribute("hope_programenrollment").getValue();
            let goal = formContext.getAttribute("hope_goal").getValue();
            let enrollment = await operationhope.CrmHelper.RetrieveRecord(enrollmentid[0].entityType, enrollmentid[0].id.replace("{", "").replace("}", ""), "_hope_program_value");

            if(enrollment._hope_program_value && goal[0]){
                let fetchXml = `
                    <fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="true">
                    <entity name="hope_actionplan">
                        <attribute name="hope_actionplanid" />
                        <attribute name="hope_name" />
                        <attribute name="createdon" />
                        <order attribute="hope_name" descending="false" />
                        <link-entity name="hope_assignedworkplan" from="hope_workplan" to="hope_actionplanid" link-type="inner" alias="ah">
                        <filter type="and">
                            <condition attribute="hope_program" operator="eq" uitype="hope_program" value="{${enrollment._hope_program_value}}" />
                        </filter>
                        <link-entity name="hope_programgoal" from="hope_programgoalid" to="hope_goal" link-type="inner" alias="ai">
                            <filter type="and">
                            <condition attribute="hope_goal" operator="eq" uitype="hope_goal" value="{${goal[0].id.replace("{", "").replace("}", "")}}" />
                            </filter>
                        </link-entity>
                        </link-entity>
                    </entity>
                    </fetch>`;

                var relatedWorkPlans = await Xrm.WebApi.retrieveMultipleRecords("hope_actionplan", "?fetchXml=" + encodeURIComponent(fetchXml));

                workPlanIds = [];

                if(relatedWorkPlans.entities){
                    relatedWorkPlans.entities.forEach(function(entity) {
                        workPlanIds.push(entity.hope_actionplanid);
                    });
                }
            }
            
        }

        function filterGoalBasedOnEnrolledProgram(){

            var formContext = executionContext;

            const baseFilter = "<filter type='or'></filter>";

            // Filter out null IDs before constructing conditions
            const filteredGoalIds = goalIds.filter(id => id !== null);

            // Construct the condition for filtering by account IDs
            const idFilterConditions = [];
            for (const goalId of filteredGoalIds) {
                const condition = `<condition attribute='hope_goalid' operator='eq' value='${goalId}'/>`;
                idFilterConditions.push(condition);
            }

            // Combine the base filter with the ID filter conditions using OR operator (if any IDs present)
            const combinedFilter = filteredGoalIds.length > 0
                ? baseFilter.replace("</filter>", "") + idFilterConditions.join("") + "</filter>"
                : baseFilter; // Use only the base filter if no valid IDs

            // Apply the combined filter to the control
            formContext.getControl("hope_goal").addCustomFilter(combinedFilter, "hope_goal");

        }

        function filterWorkPlanBasedOnGoal(){

            var formContext = executionContext;

            const baseFilter = "<filter type='or'></filter>";

            // Filter out null IDs before constructing conditions
            const filteredWorkPlanIds = workPlanIds.filter(id => id !== null);

            // Construct the condition for filtering by account IDs
            const idFilterConditions = [];
            for (const workPlanId of filteredWorkPlanIds) {
                const condition = `<condition attribute='hope_actionplanid' operator='eq' value='${workPlanId}'/>`;
                idFilterConditions.push(condition);
            }

            // Combine the base filter with the ID filter conditions using OR operator (if any IDs present)
            const combinedFilter = filteredWorkPlanIds.length > 0
                ? baseFilter.replace("</filter>", "") + idFilterConditions.join("") + "</filter>"
                : baseFilter; // Use only the base filter if no valid IDs

            // Apply the combined filter to the control
            formContext.getControl("hope_workplan").addCustomFilter(combinedFilter, "hope_actionplan");

        }

            function hideEmailFromMethodOfContact(formContext) {
                
                const methodOfContactCtrl = formContext.getControl("hope_methodofcontact");
                if (methodOfContactCtrl) {
                    methodOfContactCtrl.removeOption(368080003);
                } else {
                    console.error("The 'hope_methodofcontact' control was not found on the form.");
                }
            }
    }

    )(CoachingSessionForm = operationhope.CoachingSessionForm || (operationhope.CoachingSessionForm = {}));
}
)(operationhope || (operationhope = {}));