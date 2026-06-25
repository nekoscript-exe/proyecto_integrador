#calculadora barata con menu de seleccion de operaciones, manejo de errores para division por cero y validacion de opciones, uso de do

num1 = int(input("Digite o primeiro número: "))

num2 = int(input("Digite o segundo número: "))

print("Seleciona una opcion de operacion:")

print("""
1. Suma\n
2. Resta\n
3. Multiplicacion\n
4. Division\n
""")

opcion = int(input("Digite el numero de opcion: "))

if opcion == 1:
    resultado = num1 + num2
    print("El resultado de la suma es: ", resultado)
    
elif opcion == 2:
    resultado = num1 - num2
    print("El resultado de la resta es: ", resultado)
    
elif opcion == 3:
    resultado = num1 * num2
    print("El resultado de la multiplicacion es: ", resultado)

elif opcion == 4:
    if num2 !=0:
        resultado = num1 / num2
        print("El resultado de la division es: ", resultado)
    else:
        print("Error: No es posible dividir por cero.")
        
else:
    print("Opcion no valida. Por favor seleccione una opcion del 1 al 4.")  
    