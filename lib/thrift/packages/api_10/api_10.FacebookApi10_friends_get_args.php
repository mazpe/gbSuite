<?php
class api10_FacebookApi10_friends_get_args {
  public $uid = null;
  public $flid = null;

  public function __construct($vals=null) {
    if (is_array($vals)) {
      if (isset($vals['uid'])) {
        $this->uid = $vals['uid'];
      }
      if (isset($vals['flid'])) {
        $this->flid = $vals['flid'];
      }
    }
  }

  public function getName() {
    return 'FacebookApi10_friends_get_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::I32) {
            if (($input instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_binary_deserialize')) {
              $this->uid = thrift_protocol_binary_deserialize(TType::I32, $input);
            } else {
              $xfer += $input->readI32($this->uid);
              }
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::I64) {
            if (($input instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_binary_deserialize')) {
              $this->flid = thrift_protocol_binary_deserialize(TType::I64, $input);
            } else {
              $xfer += $input->readI64($this->flid);
              }
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('FacebookApi10_friends_get_args');
    if ($this->uid !== null) {
      $xfer += $output->writeFieldBegin('uid', TType::I32, 1);
      $xfer += $output->writeI32($this->uid);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->flid !== null) {
      $xfer += $output->writeFieldBegin('flid', TType::I64, 2);
      $xfer += $output->writeI64($this->flid);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}


?>
