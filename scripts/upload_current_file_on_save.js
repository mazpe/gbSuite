/*
 * Menu: gMan > Upload On Save
 * Kudos: Ingo Muschenetz
 * License: EPL 1.0
 * Listener: commandService().addExecutionListener(this);
 * DOM: http://localhost/com.aptana.ide.syncing
 * DOM: http://download.eclipse.org/technology/dash/update/org.eclipse.eclipsemonkey.lang.javascript
 */

// Add  * Listener: commandService().addExecutionListener(this); to the top of this file to enable it

/**
 * Returns a reference to the workspace command service
 */
function commandService()
{
   var commandServiceClass = Packages.org.eclipse.ui.commands.ICommandService;
   
   // same as doing ICommandService.class
    var commandService = Packages.org.eclipse.ui.PlatformUI.getWorkbench().getAdapter(commandServiceClass);
    return commandService;
}

/**
 * Called before any/every command is executed, so we must filter on command ID
 */
function preExecute(commandId, event) {}

/* Add in all methods required by the interface, even if they are unused */
function postExecuteSuccess(commandId, returnValue)
 {
   // if we see a save command
	  
   if (commandId == "org.eclipse.ui.window.activateEditor")      
   {
      sync.uploadCurrentEditor();
      
      /* Replace above line if you'd like to limit it to just certain projects
      var fileName = editors.activeEditor.uri;
      if(fileName.match(/projectName/ig))
      {
         sync.uploadCurrentEditor();   
      }
      */
    }
}

function notHandled(commandId, exception) 
{}

function postExecuteFailure(commandId, exception) 
{}