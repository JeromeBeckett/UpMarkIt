# UpMarkIt
A command line interface for parsing markdown to HTML written in PHP and DOS. This follows the [traditional syntax](https://daringfireball.net/projects/markdown/syntax) as created by John Gruber. The markdown.md is a file you can use to test the conversion if you want.

## Usage
These instructions are for Windows. For Mac and Linux please look up the relevant terminal commands and syntax.

There are two methods to use this software
1. Using the batch file
2. From the command line

and two ways to input the markdown
1. As a string
2. As a markdown or text file

### Prerequisites
You will need to have PHP installed on your computer and set up in your PATH environment variable to use this app. To test if this is the case open **command prompt** and enter `php -v`. If PHP version information is displayed, you are ready to use the program.

### Method 1
1. Download both the .bat and the .php files and place in the same directory.
2. Open the .bat file
3. Follow the instructions. Please note:
    + When entering filenames the file extension must be included, and they are relative to the directory holding the batch file, so it is best to include the file in the folder itself.
    + If entering a string to parse, double quotes should be avoided. Also newlines are ignored. These issues will be fixed but in the meantime it is best to use a file instead.
    
### Method 2
1. Download the .php file
2. Open command prompt
3. `cd` to the directory where UpMarkIt.php is stored. (IE `cd downloads`)
4. Command
    + Syntax:
      + `php upmarkit.php -f markdown.md [-o=output.html] [-c=true]`
      + `php upmarkit.php -s "# Test" [-o=output.html] [-c=true]`
    + Options
      + -f is the markdown file.
      + -s is the markdown string. As with method 1, double quotes should be avoided and newlines are ignored.
      + -o is the output file, omitting this option will just display the HTML in the terminal.
      + -c is whether to include the CSS for blockquotes and code blocks or not.
      + The square brackets denote that the options can omitted.
      
## Missing features/known issues
This is not 100% complete yet and so there are certain known issues and markdown features that are not available yet.

These include:
  + Ordered lists (unordered lists are fine)
  + Reference style links and images (inline style is fine)
  + Automatic links
  + Escape characters
  + Code blocks using indentation (backticks are fine)
  + Blockquotes inside lists
