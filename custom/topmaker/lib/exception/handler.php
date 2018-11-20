<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-19
 * Desc: 自定义topmaker错误处理
 */
use Exception;
use Symfony\Component\Console\Application as ConsoleApplication;

class topmaker_exception_handler implements base_contracts_exception_handler {

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        // 所有的异常都进行记录,后期可以指定
        logger::error($e);
    }

    /**
     * Render an exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     */
    public function render($request, Exception $e)
    {
        $msg = $e->getMessage();
        $url = url::action('topmaker_ctl_error@index');
        // 处理ajax请求
        if($this->ajax())
        {
            return response::json(array(
                'error' => true,
                'message'=>$msg,
                'redirect' => $url,
            ));
        }

        return redirect::away($url);
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    public function renderForConsole($output, Exception $e)
    {
        // todo: command模式exception
        (new ConsoleApplication)->renderException($e, $output);
    }

    protected function ajax()
    {
        return (request::ajax() || request::wantsJson());
    }
}