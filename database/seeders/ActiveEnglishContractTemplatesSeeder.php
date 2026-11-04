<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

/**
 * Plantillas tomadas del contrato físico de Active English (formato 2026):
 * contrato de matrícula, pagaré con su carta de instrucciones y la
 * autorización de consulta y reporte a centrales de riesgo. Se crean ya
 * publicadas; si una plantilla con el mismo código ya existe, no se toca.
 *
 * php artisan db:seed --class=ActiveEnglishContractTemplatesSeeder
 */
class ActiveEnglishContractTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            ContractTemplate::query()->firstOrCreate(
                ['code' => $template['code']],
                [
                    ...$template,
                    'version' => 1,
                    'status' => 'publicado',
                    'acceptance_mode' => 'obligatorio',
                    'requires_guardian' => false,
                    'published_at' => now(),
                ],
            );
        }
    }

    /**
     * @return list<array{code: string, name: string, type: string, scope: string, body: string}>
     */
    private function templates(): array
    {
        return [
            [
                'code' => 'active-contrato-matricula',
                'name' => 'Contrato de matrícula Active English',
                'type' => 'matricula',
                'scope' => 'matricula',
                'body' => $this->enrollmentContract(),
            ],
            [
                'code' => 'active-pagare',
                'name' => 'Pagaré y carta de instrucciones',
                'type' => 'otro',
                'scope' => 'matricula',
                'body' => $this->promissoryNote(),
            ],
            [
                'code' => 'active-centrales-riesgo',
                'name' => 'Autorización de consulta y reporte a centrales de riesgo',
                'type' => 'datos',
                'scope' => 'alumno',
                'body' => $this->creditBureauAuthorization(),
            ],
        ];
    }

    private function enrollmentContract(): string
    {
        return <<<'TEXT'
CONTRATO DE MATRÍCULA No. {{matricula.numero}}
ACTIVE ENGLISH INSTITUTE SAS – NIT 900511030-9
Cúcuta, Norte de Santander – Tel: 3183530234 - 3242485505 – www.activeenglish.edu.co

Fecha del contrato: {{fecha}}

TITULAR
Nombre: {{firmante.nombre}}
Documento: {{firmante.documento}}

ESTUDIANTE
Nombre: {{alumno.nombre}}
Documento: {{alumno.documento}}
Fecha de nacimiento: {{alumno.fecha_nacimiento}}
Dirección: {{alumno.direccion}}
Teléfono: {{alumno.telefono}}
Correo: {{alumno.correo}}

CONDICIONES ECONÓMICAS
Programa: {{curso}} – {{nivel}}
Valor del programa: {{precio_final}}
Valor de la mensualidad: {{mensualidad}}
Intensidad horaria: {{intensidad_horaria}}
Fecha de inicio: {{fecha_inicio}}
Fecha estimada de finalización: {{fecha_fin_estimada}}

Se entiende como único estudiante del sistema de aprendizaje la persona que se registra en este contrato, siempre y cuando se efectúe en el momento de la firma del presente contrato. La institución solo aceptará a usuarios mayores de seis (6) años que sepan leer y escribir para recibir las clases convenidas.

CLÁUSULAS ESPECÍFICAS DEL CONTRATO DE MATRÍCULA O DE SERVICIOS EDUCATIVOS

El presente contrato de matrícula tiene condiciones específicas y generales, las cuales las condiciones específicas se describen a continuación y las condiciones generales se podrán observar en la página web www.activeenglish.edu.co. Las cláusulas específicas serán las siguientes:

Cláusula Primera – Objeto: Una vez suscrito el presente contrato de matrícula, las partes intervinientes quedan supeditadas a las condiciones de pago y los plazos establecidos en este. Así mismo, quedan vinculados a las cláusulas específicas y generales del contrato de matrícula, así como otorga su consentimiento al cumplimiento del manual de convivencia y demás estamentos de la organización, los cuales fueron suministrados y aceptan con la firma de este contrato, y que se encuentran en la página web www.activeenglish.edu.co. En todo caso, no se podrán excusar en el desconocimiento de las cláusulas, manuales, políticas y reglamentos de la Institución, toda vez que se encuentran publicados en la página web de la institución.

Cláusula Segunda – Duración: La duración del contrato es de acuerdo con la vinculación del programa que elija el titular y/o estudiante, y está supeditado al Programa Educativo Institucional.

Cláusula Tercera – Retracto: El titular tiene el derecho al retracto conforme al artículo 47 de la Ley 1480 de 2011, que estipula: "El término máximo para ejercer el derecho de retracto será de cinco (5) días hábiles contados a partir de la entrega del bien o de la celebración del contrato en caso de la prestación de servicios".

Cláusula Cuarta – Consentimiento: Con la firma de este documento, de manera libre y voluntaria, tanto el (los) titular(es) y/o el estudiante autoriza(n) y acepta(n) circunscribirse al reglamento académico; al reglamento de conducta; a las políticas de privacidad y manejo de datos, y a las especificaciones de este contrato, los cuales son entregados con la firma de este documento, quienes aceptan haber leído y conocido previamente. Adicionalmente, se cobijarán a las políticas y reglamentos que llegase a exigir las entidades de control y vigilancia y los que puedan ser elaborados por ACTIVE ENGLISH INSTITUTE SAS de NIT 900511030-9, que serán notificados por cualquier medio y aceptarán con la firma de este documento.

Cláusula Quinta – Beneficios Económicos: Si al momento de la suscripción del presente contrato se brindaron beneficios económicos en el programa, se debe cumplir a cabalidad hasta el final de lo estipulado en el presente contrato, o de lo contrario dichos beneficios se perderán.

Cláusula Sexta – Sobre la matrícula y la Mensualidad: Cuando se acuerden pagos del programa en mensualidades, estas se deben cancelar dentro de los primeros 05 días de cada mes, directamente en la entidad bancaria o en las instalaciones de la Institución. Parágrafo 1. El valor anual de los Costos Educativos se ajustará cada año. Parágrafo 2. El retardo o mora en el pago de las mensualidades estipuladas en este contrato reconocerá intereses sobre los valores no cancelados a la tasa máxima permitida establecida por la Superintendencia Bancaria.

Cláusula Séptima – Sobre la Pérdida de la calidad de Estudiante: Será estudiante toda persona que asista a los programas ofrecidos por la Institución de manera regular; dicha calidad se pierde cuando: (I) No pague durante dos (2) meses continuos las mensualidades estipuladas en este contrato. (II) No asista por más de cuarenta y cinco (45) días a clases. (III) Por incumplimiento al manual de convivencia o a las estipulaciones del presente documento. (IV) Por el retiro voluntario del estudiante. Parágrafo 1. El Estudiante que haya abandonado el curso y desea reingresar nuevamente al programa deberá cancelar los valores de la matrícula actualizados con los respectivos valores atrasados. Parágrafo 2. En el evento en que el estudiante no asista hasta 45 días al programa, este podrá reprogramar las clases en los siguientes dos (2) meses, sin que las horas sean acumulables.

Cláusula Octava – Retiro: Por cualquier motivo que se impute a la responsabilidad del estudiante y/o titular del programa matriculado, este deberá cancelar las mensualidades atrasadas, más una mensualidad adicional y la cláusula penal por incumplimiento del contrato, a fin de expedir el respectivo paz y salvo y evitar el reporte en las centrales de riesgo.

Cláusula Novena – Causales de Terminación del Contrato: El contrato de matrícula se termina por: (I) Expiración del término fijado en el contrato. (II) Por mutuo consentimiento de las partes. (III) Por muerte del ESTUDIANTE o fuerza mayor. (IV) Por suspensión de actividades de la Institución por más de sesenta (60) días o por clausura definitiva del Establecimiento. (V) Cuando el ESTUDIANTE, sus Padres de Familia y/o Acudientes suministren información falsa para ocultar una realidad que, de haber sido conocida por la Institución antes de autorizar la matrícula de aquel, se hubiera abstenido de hacerlo. (VI) Cuando el ESTUDIANTE, sus PADRES de FAMILIA y/o ACUDIENTES procedan de mala fe contra los intereses de la Institución, sin perjuicio de que también se pueda adelantar acción penal por los mismos hechos. (VII) Por incumplimiento de los reglamentos, manuales y políticas de la Institución. (VIII) Por incumplimiento de los pagos de las mensualidades y/o erogaciones pactadas.

Cláusula Décima – Derechos y Deberes: En las condiciones generales del presente contrato quedan impresos las obligaciones, prohibiciones, deberes y derechos de los estudiantes, titulares o padres de familia y/o los de la Institución, que podrán ser consultados en la página web www.activeenglish.edu.co. En todo caso, las condiciones generales serán parte integral del presente contrato y los firmantes se obligan a conocerlo y aceptarlo al suscribir este documento.

Cláusula Décima Primera – Tratamiento de Datos Personales: Con la suscripción del presente contrato, en calidad de acudiente/padre de familia y/o estudiante, declaran de manera libre, expresa, inequívoca e informada que AUTORIZO a la Institución para que realice la recolección, almacenamiento, uso, circulación, actualización, supresión y, en general, tratamiento de mis datos personales con el fin de lograr las finalidades relativas al objeto social de la Institución Educativa. Así mismo, autorizo la transferencia de datos a las entidades públicas, privadas o administrativas en ejercicio de las competencias legales o por orden judicial; autorizo la transferencia de datos a terceros con los cuales la Institución Educativa haya celebrado contrato de prestación de servicios. Declaro que se me ha informado de manera clara y comprensible que tengo derecho a conocer, actualizar y rectificar los datos personales proporcionados, a solicitar prueba de esta autorización, a solicitar información sobre el uso que se les ha dado a mis datos personales, a presentar quejas por el uso indebido de mis datos personales, a revocar esta autorización o solicitar la supresión de los datos personales suministrados y a acceder de forma gratuita a los mismos. Leído lo anterior, declaro de manera previa, explícita e inequívoca que autorizo a ACTIVE ENGLISH INSTITUTE SAS de NIT 900511030-9 para que pueda realizar el tratamiento de los datos personales aquí diligenciados dentro de las finalidades legales, contractuales, comerciales y las aquí contempladas, de acuerdo con la Política de tratamiento de datos personales de la Institución conforme a las leyes vigentes. De igual forma, declaro ser el titular de la información suministrada en este formulario, siendo la misma completa, confiable, veraz y exacta, y manifiesto conocer mis derechos, los cuales podré ejercer a través de la página web www.activeenglish.edu.co, al WhatsApp (+57) 3183530234 y al correo electrónico contactenos@activeenglish.edu.co.

Cláusula Décima Segunda – Cláusula Penal: El incumplimiento de la totalidad o de alguna de las obligaciones derivadas de este contrato por parte de alguna de las partes dará el derecho a cobrarle y exigirle el pago de un 20% de la suma establecida en este contrato a la parte incumplida, suma que será exigible ejecutivamente desde el día siguiente del vencimiento del término pactado o desde la aceleración del plazo, o del incumplimiento de cualquiera de las obligaciones y/o cláusulas de este contrato, renunciando a los requerimientos judiciales, prejudiciales y de constitución en mora; igualmente, prestará mérito ejecutivo simplemente con la afirmación de la cláusula incumplida. Estipulando de manera expresa que se podrá cobrar la pena por el simple retardo y con la advertencia de que por el pago de la pena no se entiende extinguida la obligación principal, de acuerdo con la parte final del artículo 1594 del Código Civil. Parágrafo: Las partes responderán en caso de incumplimiento o retractación del negocio por la cláusula penal, ya que desde este momento estiman ambas partes los perjuicios que le pudieran ocasionar a la otra parte en esta suma de dinero, en donde la parte incumplida cancelará los perjuicios generados por el incumplimiento y los intereses moratorios causados.

Cláusula Décima Tercera – Mérito Ejecutivo: Las partes, de común acuerdo, pactan que el presente contrato presta mérito ejecutivo para cobrar los valores impagados, lo mismo que los intereses de mora, costos y gastos de cobranza. Por lo tanto, el presente contrato, para todos los efectos, presta mérito ejecutivo por tener incorporadas literal y autónomamente obligaciones claras, expresas y exigibles, que provienen de los deudores, de conformidad con el Art. 422 del C.G.P. Sin perjuicio de lo anterior, se suscribirá pagaré para garantizar los respectivos pagos conforme a las condiciones y cláusulas que se estipulen allí.

{{clausulas_especiales}}

Firma el titular: {{firmante.nombre}} – {{firmante.documento}}
TEXT;
    }

    private function promissoryNote(): string
    {
        return <<<'TEXT'
PAGARÉ – CONTRATO DE MATRÍCULA No. {{matricula.numero}}

El (los) suscriptor(es) identificado(s) como aparece al pie de mi (nuestra) correspondiente(s) firma(s), en virtud de este pagaré y obrando en nombre propio mediante el presente título valor, me (nos) obligo(amos) a pagar solidaria e incondicionalmente a ACTIVE ENGLISH INSTITUTE SAS de NIT 900511030-9, o a quien represente sus derechos o al tenedor legítimo del presente título valor, a cancelar en la ciudad de Cúcuta, la suma de ______________________________ ($______________ M/CTE). Que el pago total de la mencionada obligación se efectuará en un solo contado el día ____ del mes de ______________ del año ______.

Que este pagaré establece como condiciones las siguientes cláusulas: Primero. Este pagaré hará exigible y acelerará el cobro del capital de las cuotas vencidas, los intereses de mora y los intereses corrientes que se hayan ocasionado por la mora en que haya incurrido EL (LOS) DEUDOR(ES) de los servicios prestados por el Acreedor. Segundo. En caso de mora de cualquiera de las cuotas, dará lugar a ACTIVE ENGLISH INSTITUTE SAS a acelerar el plazo y reclamar por las vías judiciales la totalidad del capital vencido, junto con los correspondientes intereses moratorios liquidados a la tasa máxima legal permitida por la ley fijados por la Superintendencia Financiera de Colombia, desde el día siguiente a la fecha de exigibilidad del presente título y hasta cuando se efectúe el pago total de la deuda, así como cualquier otra suma que se derive de ella o sea complemento de ella, sin perjuicio de las demás acciones legales que EL ACREEDOR pueda ejercer para el cobro de la deuda. Tercero. Expresamente declaro excusado el protesto del presente pagaré y renuncio a los requerimientos judiciales y extrajudiciales para la constitución en mora en caso de retraso o incumplimiento total o parcial de la obligación. Cuarto. En caso de que haya lugar al recaudo judicial o extrajudicial de la obligación contenida en el presente título valor, serán de mi cargo las costas judiciales, agencias en derecho y/o los honorarios que se causen por tal razón, y que desde ahora se estiman en el cobro prejurídico en un 10% del valor total de la deuda y, en el proceso jurídico, entre el 20% y 30% más IVA, dependiendo de la etapa procesal en que se encuentre la obligación. Quinto. Los pagos que realice el DEUDOR se imputarán en el siguiente orden: honorarios, intereses moratorios y capital. Sexto. Para la exigencia judicial de este pagaré no será necesario el contrato, ni las facturas que lo respalden; se presentará el presente documento y la carta de instrucciones, el cual tendrá plena validez. Séptimo. Que desde ahora, como deudor(es), aceptamos cualquier cesión o endoso que de este pagaré haga EL ACREEDOR a cualquier persona, natural o jurídica, a quien el mencionado acreedor ceda o endose sus derechos.

Para constancia se firma en la ciudad de Cúcuta – Norte de Santander el {{fecha}}, en original para el acreedor y una copia para el deudor.

Deudor: {{firmante.nombre}} – {{firmante.documento}}

CARTA DE INSTRUCCIONES PARA DILIGENCIAR EL PAGARÉ EN BLANCO

Señores
ACTIVE ENGLISH INSTITUTE SAS de NIT 900511030-9
E.S.D.

Por medio del presente documento, yo (nosotros), como obra al pie de nuestra firma, con capacidad para contratar, en adelante EL DEUDOR, autorizo expresa, permanente e irrevocablemente a ACTIVE ENGLISH INSTITUTE SAS de NIT 900511030-9, quien en adelante se denominará EL ACREEDOR, o a quien represente sus derechos como cesionario o endosatario, para que, de conformidad con el artículo 622 del Código de Comercio, llene los espacios en blanco del pagaré adjunto que he suscrito y otorgado a la orden de EL ACREEDOR o a favor de quien represente sus derechos como cesionario o endosatario, el cual podrá ser llenado sin previo aviso, de acuerdo con las siguientes INSTRUCCIONES:

1. EL DEUDOR, para efectos de las instrucciones definidas a continuación, autoriza al ACREEDOR, por medio del presente escrito, de manera libre y voluntaria, para que sin previo aviso complete los espacios en blanco dejados en el pagaré a la orden entregado, de conformidad con lo establecido en el artículo 622 del Código de Comercio y de acuerdo con las instrucciones inmersas en este documento.

2. La fecha de vencimiento del pagaré corresponderá a aquella en que EL ACREEDOR diligencie los espacios dejados en blanco, con el fin de hacer exigible y efectuar el cobro de todas aquellas sumas de dinero que EL DEUDOR adeude y deba pagar a EL ACREEDOR por concepto de capital, intereses de mora, cláusula penal, honorarios y perjuicios causados, junto con otros conceptos tales como: el valor del impuesto de timbre causado por el título una vez sean diligenciados los espacios en blanco; los que lleguen a quedar pendientes de pago por parte de EL DEUDOR el día de su diligenciamiento, y las sumas de dinero por mí adeudadas derivadas del contrato de matrícula No. {{matricula.numero}} firmado por las partes el {{fecha}}.

3. El espacio correspondiente a la "suma cierta" será el monto de todas las sumas de dinero correspondientes a las obligaciones que, al momento de ser llenados los espacios dejados en blanco en el pagaré, EL DEUDOR adeude a favor de EL ACREEDOR.

4. Que, en los eventos definidos en el contrato de matrícula No. {{matricula.numero}} como causal de aceleración del plazo pactado inicialmente para el pago de lo debido, el ACREEDOR está facultado para diligenciar el pagaré y hacerlo efectivo por el total de las obligaciones causadas por el citado contrato, aun las no vencidas, por las cuales se acepta exigibilidad anticipada, y por cualquier otra suma generada en virtud del referido contrato hasta su cancelación total, y adicionalmente en los siguientes eventos: • Por el incumplimiento de cualquiera de las obligaciones pactadas en el contrato. • Si EL DEUDOR inicia voluntariamente o por iniciativa de un tercero proceso de liquidación o insolvencia. • Cuando cualquiera de los giradores o suscriptores llegare a ser (I) vinculado por parte de las autoridades competentes a cualquier tipo de investigación por delitos de narcotráfico, terrorismo, secuestro, lavado de activos, financiación del terrorismo y administración de recursos relacionados con actividades terroristas u otros delitos relacionados con el lavado de activos y financiación del terrorismo; (II) incluido en listas para el control de lavado de activos y financiación del terrorismo administradas por cualquier autoridad nacional o extranjera, tales como la lista de la Oficina de Control de Activos en el Exterior – OFAC emitida por la Oficina del Tesoro de los Estados Unidos de Norte América, la lista de la Organización de las Naciones Unidas y otras listas públicas relacionadas con el tema del lavado de activos y financiación del terrorismo; o (III) condenado por parte de las autoridades competentes en cualquier tipo de proceso judicial relacionado con la comisión de los anteriores delitos. En cualquiera de los anteriores eventos, quedará de pleno derecho EL DEUDOR constituido en mora por el saldo total de los valores adeudados al ACREEDOR, y esta compañía podrá declarar extinguido el plazo fijado para el pago de la totalidad de las acreencias a mi cargo y exigir judicial o extrajudicialmente el pago del saldo insoluto de las mismas.

5. En caso de no diligenciarse el lugar y fecha de creación del pagaré, esta corresponderá a la de la prestación del servicio.

6. El espacio en blanco destinado a indicar el lugar de pago de la obligación corresponderá a la dirección y a la ciudad del domicilio principal de ACTIVE ENGLISH INSTITUTE SAS de NIT 900511030-9.

7. Declaro que las instrucciones y demás autorizaciones que por medio de la presente carta han sido dadas a EL ACREEDOR se entenderán de igual modo otorgadas al tenedor legítimo del Pagaré, o a quien en el futuro ostente la calidad de Acreedor. El pagaré así llenado prestará mérito ejecutivo y será exigible sin más requisitos ni requerimientos.

8. Declaro además que conozco la totalidad del pagaré suscrito, el cual firmo sin reserva alguna, y que he recibido copia de este y de la presente Carta de Instrucciones.

9. Como DEUDOR autorizo de manera permanente e irrevocable a EL ACREEDOR, o a quien represente sus derechos, para que desde el momento de la solicitud, y con fines estadísticos, de control, supervisión y de información comercial a otras entidades, procese, reporte, conserve, consulte, suministre, actualice o efectúe el tratamiento de toda la información referente a mi comportamiento como deudor, así como cualquier información de carácter financiero y comercial, a las centrales de información o bases de datos debidamente constituidas que estime conveniente, en los términos y durante el tiempo que los sistemas de bases de datos, las normas y las autoridades lo establezcan. La consecuencia de esta autorización será la inclusión de mis datos en las mencionadas bases de datos y, por tanto, las entidades del sector financiero o de cualquier otro sector afiliadas a dichas centrales conocerán mi comportamiento presente y pasado relacionado con mis obligaciones financieras o cualquier otro dato personal o económico que estime pertinente.

Para constancia se firma en la ciudad de Cúcuta el {{fecha}}, en original para el acreedor y una copia para el deudor.

Deudor: {{firmante.nombre}} – {{firmante.documento}}
TEXT;
    }

    private function creditBureauAuthorization(): string
    {
        return <<<'TEXT'
AUTORIZACIÓN PARA LA CONSULTA, REPORTE Y PROCESAMIENTO DE DATOS CREDITICIOS, FINANCIEROS, COMERCIALES, DE SERVICIOS Y DE TERCEROS PAÍSES EN LA CENTRAL DE INFORMACIÓN CIFIN/TRANSUNIÓN/DATACRÉDITO

En nuestra calidad de titulares de información, actuando libre y voluntariamente, autorizamos de manera expresa e irrevocable a ACTIVE ENGLISH INSTITUTE NIT 900511030-9, o a quien represente sus derechos, a consultar, solicitar, suministrar, reportar, procesar y divulgar toda la información que se refiera a mi comportamiento crediticio, financiero, comercial, de servicios y de terceros países de la misma naturaleza a la Central de Información CIFIN/TRANSUNIÓN – DATACRÉDITO y las demás centrales de riesgo, y que administra la Asociación Bancaria y de Entidades Financieras de Colombia, o a quien represente sus derechos. Lo que refiere a:

a) Consultar, en cualquier tiempo, en la Central de Información de riesgo toda la información relevante para conocer mi desempeño como deudor, mi capacidad de pago o para valorar el riesgo futuro de concederme un crédito.

b) Reportar a cualquier central de información de riesgo legalmente autorizada datos, tratados o sin tratar, tanto sobre el cumplimiento oportuno como sobre el incumplimiento, si lo hubiere, de mis obligaciones crediticias o de mis deberes legales de contenido patrimonial, de tal forma que estas presenten una información veraz, pertinente, completa, actualizada y exacta de mi desempeño como deudor.

c) Suministrar a cualquier central de información de riesgo legalmente autorizada datos relativos a mis solicitudes de crédito, así como otros atinentes a mis relaciones comerciales, financieras y en general socioeconómicas que yo haya entregado o que consten en registros públicos, bases de datos públicas o documentos públicos.

En consecuencia, quienes se encuentren afiliados y/o tengan acceso a la Central de Información CIFIN – TRANSUNIÓN y/o DATACRÉDITO podrán conocer esta información de acuerdo con la legislación y jurisprudencia aplicable. Para efectos del tratamiento de datos, se aplicará el principio de legalidad en el uso, captura, recolección y tratamiento de datos personales de conformidad con la legislación y jurisprudencia aplicable que rigen el tratamiento de datos personales y los derechos fundamentales, los artículos 15 y 20 de la Constitución Política y lo ordenado en la Ley 1581 de 2012, el Decreto 1377 de 2013, el Decreto 886 de 2014 y el Decreto 1074 de 2015. Mis derechos y obligaciones, así como la permanencia de mi información en las bases de datos, corresponden a lo determinado por el ordenamiento jurídico aplicable, del cual, por ser de carácter público, estoy enterado. Así mismo, manifiesto que conozco el contenido del reglamento de la CIFIN y/o TRANSUNIÓN y/o DATACRÉDITO.

En caso de que, en el futuro, el autorizado en este documento efectúe, a favor de un tercero, una venta de cartera o una cesión a cualquier título de las obligaciones a mi cargo, los efectos de la presente autorización se extenderán a este en los mismos términos y condiciones. Así mismo, autorizo a la Central de Información a que, en su calidad de operador, ponga mi información a disposición de otros operadores nacionales o extranjeros, en los términos que establece la ley, siempre y cuando su objeto sea similar al aquí establecido.

Conocemos que el alcance de esta autorización implica que los comportamientos frente a nuestras obligaciones adquiridas con ACTIVE ENGLISH INSTITUTE serán registrados con el objeto de suministrar información suficiente y adecuada al mercado sobre el estado de nuestras obligaciones financieras, comerciales, crediticias, de servicios y las provenientes de terceros países de la misma naturaleza. En consecuencia, quienes se encuentren afiliados y/o tengan acceso a la Central de Información CIFIN/DATACRÉDITO podrán conocer esta información, de conformidad con la legislación y jurisprudencia aplicable.

Fecha: {{fecha}}

Titular: {{firmante.nombre}} – {{firmante.documento}}
TEXT;
    }
}
