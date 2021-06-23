# Example app for EGroupware development

#### 1. Step: [minimal "Hello World" app](https://github.com/EGroupware/example/tree/step1)
#### 2. Step: [an edit dialog](https://github.com/EGroupware/example/tree/step2)
#### 3. Step: [create a database table for data persistence](https://github.com/EGroupware/example/tree/step3)
#### 4. Step: [add UI to list hosts](https://github.com/EGroupware/example/tree/step4)
#### 5. Step: [Linking with other EGroupware entries and attaching files](https://github.com/EGroupware/example/tree/step5)
#### 6. Step: [Client-side actions in JavaScript and eTemplate dialogs](https://github.com/EGroupware/example/tree/step6)
#### 7. Step: [A TypeScript calulator](https://github.com/EGroupware/example/tree/step7)

#### ToDo: Description

Step 7: A TypeScript calculator (Work in progress!)

(Picture of calculator)

First of all we start of by creating a calculator.xet file which contains our number- and operating buttons as well as 2 textboxes. More on them later

In addition we create a app.css file to pretty up our calculator.

![image](https://user-images.githubusercontent.com/79371575/123094730-8979a680-d42d-11eb-891a-9149aff5bc8c.png)

To call our calculator, we add a button in the index.xet file. The button has an onclick event attached, which will call the "calculator" method in our app.ts file. The calculator method creates a dialog. In this dialog our calculator will be displayed.

(picture of code from index file)
(picture of code from app.ts file)

Now everything has been set up, we get to the actual calculating:

The first step to make a calculator is that we need to think about what determines a basic calculation. In the most basic way a calculation is about 2 numbers which will be computed based on a given operator (x + y, x - y, x * y, ...)

So in our app.ts file inside the ExampleApp class we create our 2 operand variables - "previousOperand" and "currentOperand" - as well as an "operator" variable and set them as empty strings in the constructor. The operand variables are strings, because we need strings to apend numbers from the number buttons. Also the textboxes require strings.

(picture of code)

We need 5 Methods in total to make our calculator work:

The calculatorUpdateDisplay method will set the content of the 2 textboxes from the calculator.xet file to the content from the 2 operand variables we declared before.

The calculatorClear method will set the 2 operand variables as well as the operand variable back to empty strings.

The calculatorNumber method is the method that the number buttons call when they are clicked. The method will apend the number of the button to the currentOperand variable.

The chooseOperation method is the method that the operator buttons call when they are clicked. The method basically sets the operator variable based on the clicked button and saving the content from the currentOperand variable in the previousOperand variable, while setting the value of currentOperand to an empty string. 

the calculate method turns the 2 operand variables to floats, so we can actually calculate 2 numbers. Based on the operator variable, the method calculates those 2 numbers and set them back to strings. 

...



--> [continue to step 8](https://github.com/EGroupware/example/tree/step8) by checking out branch ```step8``` in your workingcopy
